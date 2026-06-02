<?php

declare(strict_types=1);

namespace App\Observers;

use App\Contracts\Billing\CreditLedger;
use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;

class UserObserver
{
    public function created(User $user): void
    {
        $user->creditAccount()->firstOrCreate([], ['balance' => 0]);

        $amount = (int) app(FeatureAccessService::class)->limit($user, 'monthly_credits');

        if ($amount > 0) {
            app(CreditLedger::class)->grant(
                user: $user,
                amount: $amount,
                reason: 'registration_grant',
                meta: ['plan' => $user->plan->value],
            );
        }
    }
}
