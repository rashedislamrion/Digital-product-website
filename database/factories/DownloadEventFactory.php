<?php

namespace Database\Factories;

use App\Models\DownloadEvent;
use App\Models\DownloadGrant;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadEventFactory extends Factory
{
    protected $model = DownloadEvent::class;

    public function definition(): array
    {
        return [
            'download_grant_id' => DownloadGrant::factory(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'downloaded_at' => now()->subMinutes(fake()->numberBetween(1, 1000)),
            'bytes_transferred' => fake()->numberBetween(10_000_000, 35_000_000),
        ];
    }
}
