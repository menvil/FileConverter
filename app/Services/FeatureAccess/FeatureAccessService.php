<?php

declare(strict_types=1);

namespace App\Services\FeatureAccess;

use App\Models\User;
use BackedEnum;

final class FeatureAccessService
{
    public function allows(User $user, string $feature): bool
    {
        $plan = $this->planKey($user);

        return (bool) config("feature-access.plans.{$plan}.features.{$feature}", false);
    }

    public function limit(User $user, string $limit): int|string|null
    {
        $plan = $this->planKey($user);

        return config("feature-access.plans.{$plan}.limits.{$limit}");
    }

    public function limits(User $user): PlanLimit
    {
        $plan = $this->planKey($user);

        return PlanLimit::fromArray(config("feature-access.plans.{$plan}.limits"));
    }

    private function planKey(User $user): string
    {
        $plan = $user->plan;

        return $plan instanceof BackedEnum ? $plan->value : (string) $plan;
    }
}
