<?php

namespace App\Billing\Webhooks;

use App\Contracts\Billing\CreditLedger;
use App\Models\CreditAccount;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\CreditPackRepository;
use Illuminate\Support\Facades\DB;

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
        $checkoutSessionId = $session['id'] ?? null;

        if (! $userId || ! $packKey || ! $checkoutSessionId) {
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

        $payloadPriceId = $metadata['price_id'] ?? null;

        DB::transaction(function () use ($user, $pack, $event, $session, $checkoutSessionId, $payloadPriceId): void {
            // Lock the credit account row to serialize concurrent Stripe webhook
            // retries for the same user — prevents double-grant on parallel delivery.
            CreditAccount::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            if ($this->alreadyGrantedForCheckoutSession($user, $checkoutSessionId)) {
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
                    'stripe_checkout_session_id' => $checkoutSessionId,
                    'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                    'stripe_customer_id' => $session['customer'] ?? null,
                    'stripe_price_id' => $payloadPriceId ?: $pack->stripePriceId,
                ],
            );
        });
    }

    private function alreadyGrantedForCheckoutSession(User $user, string $checkoutSessionId): bool
    {
        return CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('reason', 'credit_pack_purchase')
            ->where('metadata_json->stripe_checkout_session_id', $checkoutSessionId)
            ->exists();
    }
}
