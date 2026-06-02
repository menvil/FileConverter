<?php

use App\Billing\Webhooks\SubscriptionWebhookHandler;

it('resolves subscription webhook handler from container', function () {
    expect(app(SubscriptionWebhookHandler::class))->toBeInstanceOf(SubscriptionWebhookHandler::class);
});
