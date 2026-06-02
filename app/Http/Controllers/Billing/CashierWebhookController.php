<?php

namespace App\Http\Controllers\Billing;

use App\Billing\Webhooks\CreditPackWebhookHandler;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class CashierWebhookController extends WebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($payload);

        return $this->successMethod();
    }
}
