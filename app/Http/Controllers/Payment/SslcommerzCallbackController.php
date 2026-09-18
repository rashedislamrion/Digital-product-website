<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Commerce\Services\SslcommerzService;
use App\Enums\OrderStatus;
use App\Enums\WebhookEventStatus;
use App\Http\Controllers\Controller;
use App\Jobs\FulfillOrderJob;
use App\Models\Order;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SslcommerzCallbackController extends Controller
{
    public function __construct(
        protected SslcommerzService $sslcommerzService,
    ) {}

    /**
     * Handle browser return on successful gateway payment.
     */
    public function success(Request $request): RedirectResponse
    {
        $valId = (string) $request->input('val_id');
        $tranId = (string) $request->input('tran_id');

        Log::info('SSLCOMMERZ browser success callback received', [
            'val_id' => $valId,
            'tran_id' => $tranId,
        ]);

        if (empty($valId) || empty($tranId)) {
            Log::error('Missing val_id or tran_id in SSLCOMMERZ success callback', $request->all());

            return redirect()->route('checkout.payment-failed')
                ->withErrors(['payment' => 'Invalid response data received from payment gateway.']);
        }

        $order = Order::where('order_number', $tranId)->first();

        if (! $order) {
            Log::error("Order #{$tranId} not found in SSLCOMMERZ success callback");

            return redirect()->route('checkout.payment-failed')
                ->withErrors(['payment' => 'Associated order could not be located.']);
        }

        // 1. Check idempotency: Has this val_id already been verified and processed?
        $existingEvent = WebhookEvent::where('gateway', 'sslcommerz')
            ->where('event_id', $valId)
            ->first();

        if ($existingEvent && $existingEvent->status === WebhookEventStatus::Processed) {
            Log::info("SSLCOMMERZ transaction {$valId} was already processed, redirecting to confirmation.");

            return redirect()->route('orders.confirmation', ['order_number' => $order->order_number]);
        }

        // 2. MANDATORY Synchronous Server-to-Server Order Validation API call
        $validation = $this->sslcommerzService->validateOrder($valId);

        if (! $validation['isValid']) {
            Log::warning("SSLCOMMERZ validation failed for Order #{$order->order_number}: {$validation['error']}", [
                'val_id' => $valId,
                'data' => $validation['data'],
            ]);

            $order->update(['status' => OrderStatus::Failed]);

            WebhookEvent::create([
                'gateway' => 'sslcommerz',
                'event_id' => $valId,
                'event_type' => 'order.validation_failed',
                'raw_payload' => array_merge($request->all(), ['validation_error' => $validation['error']]),
                'status' => WebhookEventStatus::Failed,
                'error_message' => $validation['error'],
                'processed_at' => now(),
            ]);

            return redirect()->route('checkout.payment-failed', ['order' => $order->order_number])
                ->withErrors(['payment' => 'Gateway order verification failed. Your card was not charged.']);
        }

        // 3. Mark Order as Paid and persist immutable Webhook Event
        if ($order->status !== OrderStatus::Paid) {
            $order->update([
                'status' => OrderStatus::Paid,
                'payment_gateway' => 'sslcommerz',
            ]);

            WebhookEvent::create([
                'gateway' => 'sslcommerz',
                'event_id' => $valId,
                'event_type' => 'order.validated',
                'raw_payload' => array_merge($request->all(), ['validation_api' => $validation['data']]),
                'status' => WebhookEventStatus::Processed,
                'processed_at' => now(),
            ]);

            // Dispatch fulfillment engine
            FulfillOrderJob::dispatch($order->id);
        }

        return redirect()->route('orders.confirmation', ['order_number' => $order->order_number])
            ->with('status', 'Payment completed and verified successfully!');
    }

    /**
     * Handle browser return on failed gateway payment.
     */
    public function fail(Request $request): RedirectResponse
    {
        $tranId = (string) $request->input('tran_id');
        $error = $request->input('error') ?: 'Payment failed or declined by issuing bank.';

        Log::warning('SSLCOMMERZ payment failed callback', [
            'tran_id' => $tranId,
            'error' => $error,
        ]);

        if ($tranId) {
            $order = Order::where('order_number', $tranId)->first();
            if ($order && $order->status === OrderStatus::Pending) {
                $order->update(['status' => OrderStatus::Failed]);
            }
        }

        return redirect()->route('checkout.payment-failed', ['order' => $tranId])
            ->withErrors(['payment' => $error]);
    }

    /**
     * Handle browser return on cancelled gateway payment.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $tranId = (string) $request->input('tran_id');

        Log::info('SSLCOMMERZ payment cancelled callback', [
            'tran_id' => $tranId,
        ]);

        if ($tranId) {
            $order = Order::where('order_number', $tranId)->first();
            if ($order && $order->status === OrderStatus::Pending) {
                $order->update(['status' => OrderStatus::Failed]);
            }
        }

        return redirect()->route('checkout.payment-failed', [
            'order' => $tranId,
            'reason' => 'Transaction was cancelled by user.',
        ]);
    }

    /**
     * Asynchronous IPN listener endpoint for server-to-server gateway callbacks.
     */
    public function ipn(Request $request): JsonResponse
    {
        $valId = (string) $request->input('val_id');
        $tranId = (string) $request->input('tran_id');

        Log::info('SSLCOMMERZ IPN notification received', [
            'val_id' => $valId,
            'tran_id' => $tranId,
        ]);

        if (empty($valId) || empty($tranId)) {
            return response()->json(['error' => 'Missing val_id or tran_id'], 400);
        }

        $order = Order::where('order_number', $tranId)->first();

        if (! $order) {
            Log::error("SSLCOMMERZ IPN: Order #{$tranId} not found");

            return response()->json(['error' => 'Order not found'], 404);
        }

        // Idempotency: skip if already processed
        $existing = WebhookEvent::where('gateway', 'sslcommerz')->where('event_id', $valId)->first();
        if ($existing && $existing->status === WebhookEventStatus::Processed) {
            return response()->json(['status' => 'already_processed']);
        }

        // Mandatory server-to-server validation
        $validation = $this->sslcommerzService->validateOrder($valId);

        if (! $validation['isValid']) {
            Log::warning("SSLCOMMERZ IPN validation failed for Order #{$order->order_number}: {$validation['error']}");

            WebhookEvent::firstOrCreate(
                ['gateway' => 'sslcommerz', 'event_id' => $valId],
                [
                    'event_type' => 'order.validation_failed',
                    'raw_payload' => array_merge($request->all(), ['validation_error' => $validation['error']]),
                    'status' => WebhookEventStatus::Failed,
                    'error_message' => $validation['error'],
                    'processed_at' => now(),
                ]
            );

            return response()->json(['status' => 'validation_failed'], 422);
        }

        // Update order and persist event
        if ($order->status !== OrderStatus::Paid) {
            $order->update([
                'status' => OrderStatus::Paid,
                'payment_gateway' => 'sslcommerz',
            ]);

            WebhookEvent::firstOrCreate(
                ['gateway' => 'sslcommerz', 'event_id' => $valId],
                [
                    'event_type' => 'order.validated',
                    'raw_payload' => array_merge($request->all(), ['validation_api' => $validation['data']]),
                    'status' => WebhookEventStatus::Processed,
                    'processed_at' => now(),
                ]
            );

            FulfillOrderJob::dispatch($order->id);
        }

        return response()->json(['status' => 'success', 'order_number' => $order->order_number]);
    }
}
