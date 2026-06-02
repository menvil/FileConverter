<?php

namespace App\Billing\Gateway;

use App\Billing\CheckoutSessionDto;
use App\Data\Billing\CreditPackDto;
use App\Models\User;

final class CashierCreditPackCheckoutGateway implements CreditPackCheckoutGateway
{
    public function createCheckout(
        User $user,
        CreditPackDto $pack,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        $checkout = $user->checkout($pack->stripePriceId, [
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'user_id' => $user->id,
                'pack_key' => $pack->key,
                'pack_credits' => $pack->credits,
            ],
        ]);

        return new CheckoutSessionDto(url: $checkout->url);
    }
}
