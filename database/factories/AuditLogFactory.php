<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['product.published', 'license.revoked', 'order.refunded', 'version.uploaded']),
            'target_type' => 'App\\Models\\Product',
            'target_id' => (string) Str::ulid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata_before' => ['status' => 'quarantined'],
            'metadata_after' => ['status' => 'published'],
            'created_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ];
    }
}
