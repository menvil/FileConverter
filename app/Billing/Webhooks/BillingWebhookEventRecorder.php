<?php

namespace App\Billing\Webhooks;

use App\Models\BillingWebhookEvent;
use Illuminate\Support\Carbon;

final class BillingWebhookEventRecorder
{
    public function recordIfNew(string $provider, string $eventId, string $type, array $payload): BillingWebhookEvent
    {
        return BillingWebhookEvent::firstOrCreate(
            ['provider_event_id' => $eventId],
            [
                'provider' => $provider,
                'type' => $type,
                'payload' => $payload,
            ],
        );
    }

    public function markProcessed(BillingWebhookEvent $event): void
    {
        $event->update(['processed_at' => Carbon::now()]);
    }

    public function wasProcessed(string $provider, string $eventId): bool
    {
        return BillingWebhookEvent::query()
            ->where('provider_event_id', $eventId)
            ->whereNotNull('processed_at')
            ->exists();
    }
}
