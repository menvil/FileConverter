<?php

namespace App\Billing;

use App\Billing\Exceptions\CannotCheckoutFreePlanException;
use App\Billing\Gateway\CreditPackCheckoutGateway;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Data\Billing\CreditPackDto;
use App\Models\User;

final class BillingPaymentService
{
    public function __construct(
        private readonly SubscriptionCheckoutGateway $gateway,
        private readonly CreditPackCheckoutGateway $creditPackGateway,
    ) {}

    public function createSubscriptionCheckout(
        User $user,
        BillingPlanDto $plan,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        if (! $plan->isPaid || blank($plan->stripePriceId)) {
            throw CannotCheckoutFreePlanException::make();
        }

        return $this->gateway->createCheckout($user, $plan, $successUrl, $cancelUrl);
    }

    public function createCreditPackCheckout(
        User $user,
        CreditPackDto $pack,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        if (blank($pack->stripePriceId)) {
            throw new \InvalidArgumentException("Credit pack '{$pack->key}' has no Stripe price ID configured.");
        }

        return $this->creditPackGateway->createCheckout($user, $pack, $successUrl, $cancelUrl);
    }
}
