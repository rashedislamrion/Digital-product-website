<?php

namespace Database\Factories;

use App\Models\Price;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    protected $model = Price::class;

    public function definition(): array
    {
        $tiers = [
            ['name' => 'Single Application', 'seats' => 1, 'amount' => 2900],
            ['name' => 'Team License', 'seats' => 5, 'amount' => 7900],
            ['name' => 'Unlimited License', 'seats' => 999999, 'amount' => 19900],
        ];

        $tier = fake()->randomElement($tiers);

        return [
            'product_id' => Product::factory(),
            'license_tier_name' => $tier['name'],
            'max_activation_seats' => $tier['seats'],
            'amount_minor' => $tier['amount'],
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}
