<?php

namespace App\Billing;

use App\Billing\Exceptions\UnknownBillingPlanException;

final class BillingPlanRepository
{
    /** @return BillingPlanDto[] */
    public function all(): array
    {
        return array_map(
            fn (string $key, array $data) => $this->toDto($key, $data),
            array_keys($this->plansConfig()),
            array_values($this->plansConfig()),
        );
    }

    /** @return BillingPlanDto[] */
    public function paid(): array
    {
        return array_values(array_filter($this->all(), fn (BillingPlanDto $plan) => $plan->isPaid));
    }

    public function findOrFail(string $key): BillingPlanDto
    {
        $plans = $this->plansConfig();

        if (! array_key_exists($key, $plans)) {
            throw UnknownBillingPlanException::forKey($key);
        }

        return $this->toDto($key, $plans[$key]);
    }

    private function toDto(string $key, array $data): BillingPlanDto
    {
        return new BillingPlanDto(
            key: $key,
            label: $data['label'],
            stripePriceId: $data['stripe_price_id'],
            monthlyCredits: $data['monthly_credits'],
            isPaid: $data['is_paid'],
        );
    }

    private function plansConfig(): array
    {
        return config('billing_plans.plans', []);
    }
}
