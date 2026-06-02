<?php

namespace App\Billing\Webhooks;

use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use App\Services\Billing\CreditPackRepository;

final class CreditPackWebhookHandler
{
    public function __construct(
        private readonly CreditPackRepository $creditPackRepository,
        private readonly CreditLedger $creditLedger,
    ) {}

    public function handleCheckoutSessionCompleted(array|object $event): void
    {
        $event = is_object($event) ? (array) $event : $event;
        $session = $event['data']['object'];
        $metadata = $session['metadata'] ?? [];

        $userId = $metadata['user_id'] ?? null;
        $packKey = $metadata['pack_key'] ?? null;

        if (! $userId || ! $packKey) {
            return;
        }

        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $pack = $this->creditPackRepository->find($packKey);

        if (! $pack) {
            return;
        }

        $this->creditLedger->grant(
            user: $user,
            amount: $pack->credits,
            reason: 'credit_pack_purchase',
            meta: [
                'pack_key' => $pack->key,
                'pack_credits' => $pack->credits,
                'stripe_event_id' => $event['id'] ?? null,
                'stripe_checkout_session_id' => $session['id'] ?? null,
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                'stripe_customer_id' => $session['customer'] ?? null,
                'stripe_price_id' => $pack->stripePriceId,
            ],
        );
    }
}
