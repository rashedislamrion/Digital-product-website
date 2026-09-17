<?php

namespace Database\Factories;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        $gateway = fake()->randomElement(['paddle', 'stripe', 'sslcommerz', 'bkash']);

        return [
            'gateway' => $gateway,
            'event_id' => 'evt_'.Str::random(16),
            'event_type' => 'payment.succeeded',
            'raw_payload' => [
                'event_id' => 'evt_'.Str::random(16),
                'amount' => 4900,
                'currency' => 'USD',
                'status' => 'completed',
                'created_at' => now()->toIso8601String(),
            ],
            'status' => WebhookEventStatus::Processed,
            'error_message' => null,
            'processed_at' => now(),
        ];
    }
}
