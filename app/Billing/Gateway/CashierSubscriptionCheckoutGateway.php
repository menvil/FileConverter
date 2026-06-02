<?php

namespace App\Billing\Gateway;

use App\Billing\BillingPlanDto;
use App\Billing\CheckoutSessionDto;
use App\Models\User;

final class CashierSubscriptionCheckoutGateway implements SubscriptionCheckoutGateway
{
    public function createCheckout(
        User $user,
        BillingPlanDto $plan,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        $checkout = $user
            ->newSubscription('default', $plan->stripePriceId)
            ->checkout([
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

        return new CheckoutSessionDto(url: $checkout->url);
    }
}
