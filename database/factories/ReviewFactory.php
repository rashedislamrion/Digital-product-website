<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'order_item_id' => OrderItem::factory(),
            'rating' => fake()->numberBetween(4, 5),
            'title' => fake()->sentence(4),
            'review_text' => fake()->paragraph(2),
            'status' => ReviewStatus::Published,
            'merchant_reply' => 'Thank you for your feedback! Glad you enjoy the product.',
            'replied_at' => now(),
        ];
    }
}
