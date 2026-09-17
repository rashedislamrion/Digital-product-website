<?php

namespace Database\Factories;

use App\Enums\LicenseStatus;
use App\Models\Customer;
use App\Models\License;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        $rawKey = 'MKT-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        $maskedKey = 'MKT-XXXX-XXXX-'.substr($rawKey, -4);
        $keyHash = hash('sha256', $rawKey);

        return [
            'order_item_id' => OrderItem::factory(),
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'license_key_hash' => $keyHash,
            'license_key_masked' => $maskedKey,
            'status' => LicenseStatus::Active,
            'max_activations' => fake()->randomElement([1, 5, 999999]),
            'current_activations_count' => 0,
            'valid_until' => now()->addYear(),
        ];
    }
}
