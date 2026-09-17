<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DownloadGrant;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadGrantFactory extends Factory
{
    protected $model = DownloadGrant::class;

    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'customer_id' => Customer::factory(),
            'max_download_attempts' => 5,
            'download_count' => 0,
            'expires_at' => now()->addDays(365),
            'is_revoked' => false,
        ];
    }
}
