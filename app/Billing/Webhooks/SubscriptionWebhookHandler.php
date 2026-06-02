<?php

namespace App\Billing\Webhooks;

use App\Billing\BillingPlanRepository;
use App\Contracts\Billing\CreditLedger;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionWebhookHandler
{
    public function __construct(
        private readonly BillingPlanRepository $plans,
        private readonly CreditLedger $creditLedger,
        private readonly BillingWebhookEventRecorder $recorder,
    ) {}

    public function handleSubscriptionActivated(User $user, string $planKey, array $payload = []): void
    {
        $plan = $this->plans->findOrFail($planKey);

        $user->forceFill(['plan' => $plan->key])->save();
    }

    public function handleSubscriptionCancelled(User $user, array $payload = []): void
    {
        $user->forceFill(['plan' => 'free'])->save();
    }

    public function handleInvoicePaid(User $user, string $planKey, string $invoiceId, array $payload = []): void
    {
        $plan = $this->plans->findOrFail($planKey);

        if (! $plan->isPaid) {
            return;
        }

        DB::transaction(function () use ($user, $plan, $invoiceId, $payload): void {
            // Lock the credit account row to serialize concurrent invoice webhooks
            // for the same user; prevents double-grant from duplicate Stripe delivery.
            $user->creditAccount()->lockForUpdate()->firstOrFail();

            if ($this->alreadyGrantedForInvoice($invoiceId)) {
                return;
            }

            $this->creditLedger->grant(
                user: $user,
                amount: $plan->monthlyCredits,
                reason: 'subscription_monthly_grant',
                meta: [
                    'stripe_invoice_id' => $invoiceId,
                    'plan' => $plan->key,
                    'payload' => $payload,
                ],
            );
        });
    }

    private function alreadyGrantedForInvoice(string $invoiceId): bool
    {
        return CreditTransaction::query()
            ->where('reason', 'subscription_monthly_grant')
            ->whereRaw("json_extract(metadata_json, '$.stripe_invoice_id') = ?", [$invoiceId])
            ->exists();
    }
}
