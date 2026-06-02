<?php

namespace App\Billing\Gateway;

use App\Billing\CheckoutSessionDto;
use App\Data\Billing\CreditPackDto;
use App\Models\User;

final class FakeCreditPackCheckoutGateway implements CreditPackCheckoutGateway
{
    public function createCheckout(
        User $user,
        CreditPackDto $pack,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        return new CheckoutSessionDto(url: 'https://checkout.stripe.test/fake-credit-pack-session');
    }
}
