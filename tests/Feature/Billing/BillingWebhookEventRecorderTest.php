<?php

use App\Billing\Webhooks\BillingWebhookEventRecorder;
use App\Models\BillingWebhookEvent;

it('records billing webhook event only once', function () {
    $recorder = app(BillingWebhookEventRecorder::class);

    $first = $recorder->recordIfNew('stripe', 'evt_123', 'invoice.paid', ['id' => 'evt_123']);
    $second = $recorder->recordIfNew('stripe', 'evt_123', 'invoice.paid', ['id' => 'evt_123']);

    expect($first->wasRecentlyCreated)->toBeTrue();
    expect($second->wasRecentlyCreated)->toBeFalse();
    expect(BillingWebhookEvent::query()->where('provider_event_id', 'evt_123')->count())->toBe(1);
});

it('marks webhook event as processed', function () {
    $recorder = app(BillingWebhookEventRecorder::class);

    $event = $recorder->recordIfNew('stripe', 'evt_456', 'customer.subscription.updated', []);
    $recorder->markProcessed($event);

    expect($event->fresh()->processed_at)->not->toBeNull();
});

it('detects already processed webhook event', function () {
    $recorder = app(BillingWebhookEventRecorder::class);

    $event = $recorder->recordIfNew('stripe', 'evt_789', 'invoice.paid', []);
    expect($recorder->wasProcessed('stripe', 'evt_789'))->toBeFalse();

    $recorder->markProcessed($event);
    expect($recorder->wasProcessed('stripe', 'evt_789'))->toBeTrue();
});
