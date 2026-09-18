<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Commerce\Actions\GrantOrderEntitlements;
use App\Enums\OrderStatus;
use App\Enums\WebhookEventStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaddleWebhookController extends Controller
{
    /**
     * Replay tolerance window in seconds (@research.md §6.2).
     */
    public const REPLAY_TOLERANCE_SECONDS = 5;

    public function __construct(
        protected GrantOrderEntitlements $grantOrderEntitlements,
    ) {}

    /**
     * Handle incoming Paddle webhook notification.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $rawContent = $request->getContent();
        $signatureHeader = (string) $request->header('Paddle-Signature', '');
        $secret = config('cashier.webhook_secret') ?: env('PADDLE_WEBHOOK_SECRET', '');

        // 1. HMAC-SHA256 Signature Verification & 5-Second Replay Tolerance Window
        if (! empty($secret)) {
            $verification = $this->verifySignature($signatureHeader, $rawContent, $secret);

            if (! $verification['valid']) {
                Log::warning('Paddle webhook rejected: '.$verification['error'], [
                    'signature_header' => $signatureHeader,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => $verification['error'],
                ], 403);
            }
        }

        $payload = json_decode($rawContent, true);
        if (! is_array($payload)) {
            return response()->json(['error' => 'Malformed JSON payload'], 400);
        }

        $eventId = $payload['event_id'] ?? ('evt_fallback_'.md5($rawContent));
        $eventType = $payload['event_type'] ?? 'unknown';

        // 2. Idempotency Check in webhook_events
        $existingEvent = WebhookEvent::where('gateway', 'paddle')
            ->where('event_id', $eventId)
            ->first();

        if ($existingEvent && ($existingEvent->status === WebhookEventStatus::Processed || $existingEvent->status?->value === 'processed')) {
            return response()->json(['status' => 'already_processed', 'event_id' => $eventId], 200);
        }

        $webhookRecord = $existingEvent ?: WebhookEvent::create([
            'gateway' => 'paddle',
            'event_id' => $eventId,
            'event_type' => $eventType,
            'raw_payload' => $payload,
            'status' => WebhookEventStatus::Received,
        ]);

        // 3. Handle 'transaction.completed'
        if ($eventType === 'transaction.completed') {
            $data = $payload['data'] ?? [];
            $customData = $data['custom_data'] ?? [];
            $orderId = $customData['order_id'] ?? null;
            $orderNumber = $customData['order_number'] ?? null;

            $order = null;
            if ($orderId) {
                $order = Order::find($orderId);
            }
            if (! $order && $orderNumber) {
                $order = Order::where('order_number', $orderNumber)->first();
            }

            if (! $order) {
                $webhookRecord->update([
                    'status' => WebhookEventStatus::Failed,
                    'error_message' => "Referenced order not found (id: {$orderId}, number: {$orderNumber})",
                ]);

                Log::error('Paddle webhook: Order not found for transaction.completed', [
                    'event_id' => $eventId,
                    'custom_data' => $customData,
                ]);

                return response()->json(['error' => 'Order not found'], 404);
            }

            // Mark order paid if not already paid
            if ($order->status !== OrderStatus::Paid) {
                $order->update([
                    'status' => OrderStatus::Paid,
                    'payment_gateway' => 'paddle',
                ]);
            }

            // Execute shared entitlement action
            $this->grantOrderEntitlements->execute($order);

            $webhookRecord->update([
                'status' => WebhookEventStatus::Processed,
                'processed_at' => now(),
            ]);

            Log::info("Paddle webhook processed successfully for Order #{$order->order_number} (Event: {$eventId})");

            return response()->json([
                'status' => 'success',
                'order_number' => $order->order_number,
            ], 200);
        }

        // For other events, mark as received
        $webhookRecord->update([
            'status' => WebhookEventStatus::Processed,
            'processed_at' => now(),
        ]);

        return response()->json(['status' => 'acknowledged', 'event_type' => $eventType], 200);
    }

    /**
     * Verify Paddle HMAC-SHA256 signature with 5-second replay tolerance window.
     *
     * @return array{valid: bool, error: ?string}
     */
    public function verifySignature(string $header, string $payload, string $secret): array
    {
        if (empty($header)) {
            return ['valid' => false, 'error' => 'Missing Paddle-Signature header'];
        }

        $parsed = [];
        foreach (explode(';', $header) as $part) {
            if (str_contains($part, '=')) {
                [$key, $val] = explode('=', trim($part), 2);
                $parsed[$key] = $val;
            }
        }

        if (! isset($parsed['ts']) || ! isset($parsed['h1'])) {
            return ['valid' => false, 'error' => 'Malformed Paddle-Signature format'];
        }

        $timestamp = (int) $parsed['ts'];
        $hash = $parsed['h1'];

        // Enforce 5-second replay window per @research.md §6.2
        $timeVariance = abs(time() - $timestamp);
        if ($timeVariance > self::REPLAY_TOLERANCE_SECONDS) {
            return [
                'valid' => false,
                'error' => "Replay tolerance window exceeded (skew: {$timeVariance}s, allowed: ".self::REPLAY_TOLERANCE_SECONDS.'s)',
            ];
        }

        $signedPayload = "{$timestamp}:{$payload}";
        $expectedHash = hash_hmac('sha256', $signedPayload, $secret);

        if (! hash_equals($expectedHash, $hash)) {
            return ['valid' => false, 'error' => 'Invalid HMAC-SHA256 signature hash'];
        }

        return ['valid' => true, 'error' => null];
    }
}
