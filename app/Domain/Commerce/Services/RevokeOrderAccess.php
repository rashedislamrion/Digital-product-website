<?php

namespace App\Domain\Commerce\Services;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\DownloadGrant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevokeOrderAccess
{
    /**
     * Execute revocation of digital access and mark order as refunded.
     *
     * @param  Order  $order
     * @param  User|null  $actor
     * @param  string|null  $reason
     * @return Order
     */
    public function execute(Order $order, ?User $actor = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $previousStatus = $order->status->value;

            Log::info("RevokeOrderAccess: Initiating revocation for Order #{$order->order_number}", [
                'order_id' => $order->id,
                'actor_id' => $actor?->id ?? Auth::id(),
                'reason' => $reason,
            ]);

            // 1. Mutate order status to 'refunded'
            $order->update(['status' => OrderStatus::Refunded]);

            // 2. Revoke all related download grants
            $items = $order->items()->with(['downloadGrants', 'license.activations'])->get();
            $revokedGrantsCount = 0;
            $revokedLicensesCount = 0;

            foreach ($items as $item) {
                foreach ($item->downloadGrants as $grant) {
                    $grant->update(['is_revoked' => true]);
                    $revokedGrantsCount++;
                }

                if ($item->license) {
                    $item->license->update(['status' => LicenseStatus::Revoked]);

                    // Deactivate all active instances
                    $item->license->activations()
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'deactivated_at' => now(),
                        ]);

                    $revokedLicensesCount++;
                }
            }

            // 3. Log security event into audit_logs table
            AuditLog::create([
                'user_id' => $actor?->id ?? Auth::id(),
                'action' => 'order.refund',
                'target_type' => Order::class,
                'target_id' => $order->id,
                'ip_address' => request()?->ip() ?: '127.0.0.1',
                'user_agent' => request()?->userAgent() ?: 'Artisan / CLI',
                'metadata_before' => [
                    'status' => $previousStatus,
                ],
                'metadata_after' => [
                    'status' => OrderStatus::Refunded->value,
                    'revoked_grants_count' => $revokedGrantsCount,
                    'revoked_licenses_count' => $revokedLicensesCount,
                    'reason' => $reason ?? 'Customer requested refund / chargeback',
                ],
                'created_at' => now(),
            ]);

            Log::info("RevokeOrderAccess: Completed revocation for Order #{$order->order_number}. Revoked {$revokedGrantsCount} grants and {$revokedLicensesCount} licenses.");

            return $order->fresh(['items.downloadGrants', 'items.license']);
        });
    }
}
