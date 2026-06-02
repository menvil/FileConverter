<?php

namespace App\Billing\Webhooks;

use App\Billing\BillingPlanRepository;
use App\Contracts\Billing\CreditLedger;
use App\Models\User;

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
        throw new \LogicException('Not implemented yet');
    }

    public function handleInvoicePaid(User $user, string $planKey, string $invoiceId, array $payload = []): void
    {
        throw new \LogicException('Not implemented yet');
    }
}
