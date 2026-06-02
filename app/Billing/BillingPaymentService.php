<?php

namespace App\Billing;

use App\Billing\Exceptions\CannotCheckoutFreePlanException;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Models\User;

final class BillingPaymentService
{
    public function __construct(
        private readonly SubscriptionCheckoutGateway $gateway,
    ) {}

    public function createSubscriptionCheckout(
        User $user,
        BillingPlanDto $plan,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        if (! $plan->isPaid || $plan->stripePriceId === null) {
            throw CannotCheckoutFreePlanException::make();
        }

        return $this->gateway->createCheckout($user, $plan, $successUrl, $cancelUrl);
    }
}
