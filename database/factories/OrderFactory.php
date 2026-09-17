<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomElement([2900, 4900, 7900, 19900]);
        $tax = (int) round($subtotal * 0.05);
        $total = $subtotal + $tax;

        return [
            'order_number' => 'ORD-'.now()->format('Ym').'-'.fake()->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Paid,
            'currency' => 'USD',
            'subtotal_minor' => $subtotal,
            'discount_minor' => 0,
            'tax_minor' => $tax,
            'total_minor' => $total,
            'payment_gateway' => fake()->randomElement(['sslcommerz', 'bkash', 'paddle', 'stripe']),
        ];
    }
}
