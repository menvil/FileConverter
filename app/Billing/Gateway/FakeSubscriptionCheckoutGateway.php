<?php

namespace App\Billing\Gateway;

use App\Billing\BillingPlanDto;
use App\Billing\CheckoutSessionDto;
use App\Models\User;

final class FakeSubscriptionCheckoutGateway implements SubscriptionCheckoutGateway
{
    public function createCheckout(
        User $user,
        BillingPlanDto $plan,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        return new CheckoutSessionDto(url: 'https://checkout.stripe.test/fake-session');
    }
}
