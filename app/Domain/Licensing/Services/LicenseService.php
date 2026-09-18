<?php

namespace App\Domain\Licensing\Services;

use App\Enums\LicenseStatus;
use App\Models\Customer;
use App\Models\License;
use App\Models\OrderItem;
use Illuminate\Support\Str;

class LicenseService
{
    /**
     * Generate a cryptographically secure license key matching the format:
     * PROD-XXXX-XXXX-XXXX-XXXX
     */
    public function generateKey(string $prefix = 'PROD'): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsLen = strlen($chars);
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $segment = '';
            $bytes = random_bytes(4);
            for ($j = 0; $j < 4; $j++) {
                $segment .= $chars[ord($bytes[$j]) % $charsLen];
            }
            $segments[] = $segment;
        }

        return $prefix.'-'.implode('-', $segments);
    }

    /**
     * Compute SHA-256 hash of a raw license key for DB indexing and lookup.
     */
    public function hashKey(string $rawKey): string
    {
        return hash('sha256', trim($rawKey));
    }

    /**
     * Generate a masked display representation of the key (e.g. PROD-XXXX-XXXX-XXXX-88E0).
     */
    public function maskKey(string $rawKey): string
    {
        $trimmed = trim($rawKey);
        $prefix = Str::before($trimmed, '-');
        $lastSegment = Str::afterLast($trimmed, '-');

        return $prefix.'-XXXX-XXXX-XXXX-'.$lastSegment;
    }

    /**
     * Create a license for an order item, returning the newly created License and raw key.
     * The raw key is returned ONLY once and must not be persisted in plaintext.
     *
     * @return array{license: License, raw_key: string}
     */
    public function createLicenseForOrderItem(OrderItem $orderItem, Customer $customer, ?int $maxActivations = null): array
    {
        $rawKey = $this->generateKey();
        $keyHash = $this->hashKey($rawKey);
        $keyMasked = $this->maskKey($rawKey);

        $seats = $maxActivations
            ?? $orderItem->price?->max_activation_seats
            ?? 1;

        $license = License::create([
            'order_item_id' => $orderItem->id,
            'customer_id' => $customer->id,
            'product_id' => $orderItem->product_id,
            'license_key_hash' => $keyHash,
            'license_key_masked' => $keyMasked,
            'status' => LicenseStatus::Issued,
            'max_activations' => $seats,
            'current_activations_count' => 0,
            'valid_until' => null, // Perpetual license
        ]);

        return [
            'license' => $license,
            'raw_key' => $rawKey,
        ];
    }

    /**
     * Activate an instance against a license key.
     *
     * @return array{success: bool, code?: int, error?: string, data?: array}
     */
    public function activate(string $rawKey, string $instanceFingerprint, ?string $hostname = null, ?string $ipAddress = null): array
    {
        $hash = $this->hashKey($rawKey);
        $license = License::where('license_key_hash', $hash)->first();

        if (! $license) {
            return [
                'success' => false,
                'code' => 404,
                'error' => 'License key not found or invalid.',
            ];
        }

        if (in_array($license->status, [LicenseStatus::Revoked, LicenseStatus::Suspended, LicenseStatus::Expired])) {
            return [
                'success' => false,
                'code' => 422,
                'error' => "License is {$license->status->value}.",
            ];
        }

        if ($license->valid_until && $license->valid_until->isPast()) {
            return [
                'success' => false,
                'code' => 422,
                'error' => 'License has expired.',
            ];
        }

        // Check if instance is already active for this license
        $existing = $license->activations()
            ->where('instance_fingerprint', $instanceFingerprint)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return [
                'success' => true,
                'data' => [
                    'valid' => true,
                    'instance_id' => $existing->id,
                    'activations_remaining' => max(0, $license->max_activations - $license->current_activations_count),
                    'expires_at' => $license->valid_until?->toIso8601String(),
                ],
            ];
        }

        // Check activation seat limit
        if ($license->current_activations_count >= $license->max_activations) {
            return [
                'success' => false,
                'code' => 422,
                'error' => 'Maximum activation seats reached for this license.',
            ];
        }

        // Record activation
        $activation = $license->activations()->create([
            'instance_fingerprint' => $instanceFingerprint,
            'hostname' => $hostname,
            'ip_address' => $ipAddress,
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $license->increment('current_activations_count');

        // Transition from 'issued' to 'active' on first activation
        if ($license->status === LicenseStatus::Issued) {
            $license->update(['status' => LicenseStatus::Active]);
        }

        return [
            'success' => true,
            'data' => [
                'valid' => true,
                'instance_id' => $activation->id,
                'activations_remaining' => max(0, $license->max_activations - $license->current_activations_count),
                'expires_at' => $license->valid_until?->toIso8601String(),
            ],
        ];
    }

    /**
     * Validate an existing instance activation.
     *
     * @return array{success: bool, code?: int, error?: string, data?: array}
     */
    public function validate(string $rawKey, string $instanceFingerprint): array
    {
        $hash = $this->hashKey($rawKey);
        $license = License::with(['orderItem.version', 'orderItem.price', 'product.latestPublishedVersion'])
            ->where('license_key_hash', $hash)
            ->first();

        if (! $license) {
            return [
                'success' => false,
                'code' => 404,
                'error' => 'License key not found or invalid.',
            ];
        }

        if ($license->status !== LicenseStatus::Active) {
            return [
                'success' => false,
                'code' => 422,
                'error' => "License is {$license->status->value}.",
            ];
        }

        if ($license->valid_until && $license->valid_until->isPast()) {
            return [
                'success' => false,
                'code' => 422,
                'error' => 'License has expired.',
            ];
        }

        $activation = $license->activations()
            ->where('instance_fingerprint', $instanceFingerprint)
            ->where('is_active', true)
            ->first();

        if (! $activation) {
            return [
                'success' => false,
                'code' => 422,
                'error' => 'Instance fingerprint not activated for this license.',
            ];
        }

        $tier = $license->orderItem?->historical_tier_name
            ?? $license->orderItem?->price?->license_tier_name
            ?? 'Standard';

        $productVersion = $license->orderItem?->version?->version_number
            ?? $license->product?->latestPublishedVersion?->version_number
            ?? '1.0.0';

        return [
            'success' => true,
            'data' => [
                'valid' => true,
                'status' => $license->status->value,
                'tier' => $tier,
                'product_version' => $productVersion,
            ],
        ];
    }

    /**
     * Deactivate an instance, decrementing active activation seats.
     *
     * @return array{success: bool, code?: int, error?: string, data?: array}
     */
    public function deactivate(string $rawKey, string $instanceFingerprint): array
    {
        $hash = $this->hashKey($rawKey);
        $license = License::where('license_key_hash', $hash)->first();

        if (! $license) {
            return [
                'success' => false,
                'code' => 404,
                'error' => 'License key not found or invalid.',
            ];
        }

        $activation = $license->activations()
            ->where('instance_fingerprint', $instanceFingerprint)
            ->where('is_active', true)
            ->first();

        if (! $activation) {
            return [
                'success' => false,
                'code' => 404,
                'error' => 'Active instance activation not found for this fingerprint.',
            ];
        }

        $activation->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        $newCount = max(0, $license->current_activations_count - 1);
        $license->update(['current_activations_count' => $newCount]);

        return [
            'success' => true,
            'data' => [
                'deactivated' => true,
                'activations_remaining' => max(0, $license->max_activations - $newCount),
            ],
        ];
    }
}
