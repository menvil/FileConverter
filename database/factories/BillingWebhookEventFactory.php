<?php

namespace Database\Factories;

use App\Models\BillingWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingWebhookEvent>
 */
class BillingWebhookEventFactory extends Factory
{
    protected $model = BillingWebhookEvent::class;

    public function definition(): array
    {
        return [
            'provider' => 'stripe',
            'provider_event_id' => 'evt_'.$this->faker->unique()->lexify('????????????????'),
            'type' => 'invoice.paid',
            'payload' => [],
            'processed_at' => null,
        ];
    }
}
