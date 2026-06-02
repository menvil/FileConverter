<?php

use App\Models\BillingWebhookEvent;
use Illuminate\Database\QueryException;

it('can store billing webhook event', function () {
    $event = BillingWebhookEvent::create([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_test_123',
        'type' => 'invoice.paid',
        'payload' => ['id' => 'evt_test_123'],
        'processed_at' => null,
    ]);

    expect($event->exists)->toBeTrue();
});

it('enforces unique provider event id', function () {
    BillingWebhookEvent::create([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_unique_test',
        'type' => 'invoice.paid',
        'payload' => [],
    ]);

    BillingWebhookEvent::create([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_unique_test',
        'type' => 'invoice.paid',
        'payload' => [],
    ]);
})->throws(QueryException::class);
