<?php

namespace App\Billing\Webhooks;

final class CreditPackWebhookHandler
{
    public function handleCheckoutSessionCompleted(array|object $event): void
    {
        throw new \LogicException('Not implemented yet.');
    }
}
