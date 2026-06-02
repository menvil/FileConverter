<?php

declare(strict_types=1);

use App\Billing\Webhooks\CreditPackWebhookHandler;

it('has credit pack webhook handler with checkout completed method', function () {
    $handler = app(CreditPackWebhookHandler::class);

    expect(method_exists($handler, 'handleCheckoutSessionCompleted'))->toBeTrue();
});
