<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_version_id' => ProductVersion::factory(),
            'price_id' => Price::factory(),
            'historical_product_title' => fake()->catchPhrase(),
            'historical_tier_name' => 'Single Application License',
            'unit_amount_minor' => 4900,
        ];
    }
}
