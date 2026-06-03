<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Billing\BillingPaymentService;
use App\Billing\BillingPlanRepository;
use App\Billing\CheckoutSessionDto;
use App\Enums\Plan;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class StartSubscriptionCheckoutAction
{
    public function __construct(
        private readonly BillingPlanRepository $planRepository,
        private readonly BillingPaymentService $paymentService,
    ) {}

    public function handle(User $user, string $planKey, string $successUrl, string $cancelUrl): CheckoutSessionDto
    {
        try {
            $plan = Plan::from($planKey);
        } catch (\ValueError) {
            throw ValidationException::withMessages(['plan' => 'Invalid plan selected.']);
        }

        if ($plan === Plan::Free) {
            throw ValidationException::withMessages(['plan' => 'Free plan does not require checkout.']);
        }

        if ($user->plan === $plan) {
            throw ValidationException::withMessages(['plan' => 'You are already on this plan.']);
        }

        $billingPlan = $this->planRepository->findOrFail($planKey);

        return $this->paymentService->createSubscriptionCheckout(
            user: $user,
            plan: $billingPlan,
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
        );
    }
}
