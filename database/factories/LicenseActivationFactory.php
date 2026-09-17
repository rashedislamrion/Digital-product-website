<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LicenseActivationFactory extends Factory
{
    protected $model = LicenseActivation::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'instance_fingerprint' => hash('sha256', Str::random(32)),
            'hostname' => fake()->domainName(),
            'ip_address' => fake()->ipv4(),
            'is_active' => true,
            'activated_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'deactivated_at' => null,
        ];
    }
}
