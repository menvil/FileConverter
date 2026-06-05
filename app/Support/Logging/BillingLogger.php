<?php

declare(strict_types=1);

namespace App\Support\Logging;

use App\Models\User;
use Illuminate\Support\Facades\Log;

final class BillingLogger
{
    public function creditsGranted(User $user, int $amount, string $reason): void
    {
        Log::info('credits.granted', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    public function creditsSpent(User $user, int $amount, string $reason): void
    {
        Log::info('credits.spent', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    public function creditsRefunded(User $user, int $amount, string $reason): void
    {
        Log::info('credits.refunded', [
            'user_id' => $user->id,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    public function subscriptionUpdated(int|string $userId, string $stripePlanId): void
    {
        Log::info('billing.subscription.updated', [
            'user_id' => $userId,
            'stripe_plan_id' => $stripePlanId,
        ]);
    }

    public function creditPackPurchased(int|string $userId, string $sessionId): void
    {
        Log::info('billing.credit_pack.purchased', [
            'user_id' => $userId,
            'stripe_checkout_session_id' => $sessionId,
        ]);
    }
}
