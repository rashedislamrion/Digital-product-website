<?php

namespace App\Domain\Commerce\Actions;

use App\Domain\Licensing\Services\LicenseService;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Jobs\WatermarkPdfProductJob;
use App\Mail\OrderConfirmationMail;
use App\Models\DownloadGrant;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GrantOrderEntitlements
{
    public function __construct(
        protected LicenseService $licenseService,
    ) {}

    /**
     * Execute the entitlement granting process for a paid order.
     *
     * @return array{grants_created: int, licenses_created: int, raw_licenses: array}
     */
    public function execute(Order|string $order): array
    {
        $orderModel = is_string($order)
            ? Order::with(['customer', 'items.product.latestPublishedVersion', 'items.price', 'items.downloadGrants', 'items.license'])->find($order)
            : $order->loadMissing(['customer', 'items.product.latestPublishedVersion', 'items.price', 'items.downloadGrants', 'items.license']);

        if (! $orderModel) {
            Log::error('GrantOrderEntitlements: Order not found', ['order' => $order]);

            return ['grants_created' => 0, 'licenses_created' => 0, 'raw_licenses' => []];
        }

        if ($orderModel->status !== OrderStatus::Paid) {
            Log::warning('GrantOrderEntitlements: Order is not paid yet, skipping entitlement grant', [
                'order_id' => $orderModel->id,
                'status' => $orderModel->status->value,
            ]);

            return ['grants_created' => 0, 'licenses_created' => 0, 'raw_licenses' => []];
        }

        Log::info("GrantOrderEntitlements: Processing fulfillment for Order #{$orderModel->order_number}", [
            'order_id' => $orderModel->id,
            'customer_email' => $orderModel->customer->email,
            'items_count' => $orderModel->items->count(),
        ]);

        $grantsCreated = 0;
        $licensesCreated = 0;
        $rawLicenses = [];

        foreach ($orderModel->items as $item) {
            // 1. Link latest published version if not already locked
            if (! $item->product_version_id && $item->product?->latestPublishedVersion) {
                $item->update([
                    'product_version_id' => $item->product->latestPublishedVersion->id,
                ]);
            }

            // 2. Generate DownloadGrant (idempotent: check if already exists)
            $existingGrant = DownloadGrant::where('order_item_id', $item->id)->first();
            if (! $existingGrant) {
                $grant = DownloadGrant::create([
                    'order_item_id' => $item->id,
                    'customer_id' => $orderModel->customer_id,
                    'max_download_attempts' => 5,
                    'download_count' => 0,
                    'expires_at' => now()->addYear(),
                    'is_revoked' => false,
                ]);
                $grantsCreated++;
            } else {
                $grant = $existingGrant;
            }

            // 3. Generate Cryptographic License if product is software
            $isSoftware = $item->product?->product_type === ProductType::Software;
            if ($isSoftware && ! $item->license) {
                $licenseResult = $this->licenseService->createLicenseForOrderItem(
                    $item,
                    $orderModel->customer,
                    $item->price?->max_activation_seats,
                );

                $rawLicenses[$item->id] = [
                    'raw_key' => $licenseResult['raw_key'],
                    'product' => $item->historical_product_title,
                ];
                $licensesCreated++;
            }

            // 4. Pre-queue PDF watermarking for ebook products
            if ($item->product?->product_type === ProductType::Ebook && class_exists(WatermarkPdfProductJob::class)) {
                try {
                    WatermarkPdfProductJob::dispatch($grant->id);
                } catch (\Throwable $e) {
                    Log::warning('GrantOrderEntitlements: Could not dispatch PDF watermarking job', [
                        'grant_id' => $grant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Cache raw keys briefly (30 min) so customer confirmation screen can display it once
        if (! empty($rawLicenses)) {
            Cache::put("order_raw_licenses_{$orderModel->id}", $rawLicenses, now()->addMinutes(30));
        }

        // 5. Dispatch queued Order Confirmation email
        try {
            Mail::to($orderModel->customer->email)->queue(
                new OrderConfirmationMail(
                    $orderModel->fresh(['items.product', 'items.downloadGrants', 'items.license']),
                    $rawLicenses,
                )
            );
        } catch (\Throwable $e) {
            Log::error('GrantOrderEntitlements: Failed to dispatch order confirmation email', [
                'order_id' => $orderModel->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info("GrantOrderEntitlements: Completed successfully for Order #{$orderModel->order_number}.");

        return [
            'grants_created' => $grantsCreated,
            'licenses_created' => $licensesCreated,
            'raw_licenses' => $rawLicenses,
        ];
    }
}
