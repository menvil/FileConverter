<?php

namespace App\Billing\Gateway;

use App\Billing\BillingPlanDto;
use App\Billing\CheckoutSessionDto;
use App\Models\User;

interface SubscriptionCheckoutGateway
{
    public function createCheckout(
        User $user,
        BillingPlanDto $plan,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto;
}
