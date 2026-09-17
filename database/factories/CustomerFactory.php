<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'country_code' => fake()->randomElement(['BD', 'US', 'GB', 'DE', 'AU']),
            'tax_identifier' => fake()->optional()->numerify('VAT-########'),
        ];
    }
}
