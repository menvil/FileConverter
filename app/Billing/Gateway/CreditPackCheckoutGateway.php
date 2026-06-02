<?php

namespace App\Billing\Gateway;

use App\Billing\CheckoutSessionDto;
use App\Data\Billing\CreditPackDto;
use App\Models\User;

interface CreditPackCheckoutGateway
{
    public function createCheckout(
        User $user,
        CreditPackDto $pack,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto;
}
