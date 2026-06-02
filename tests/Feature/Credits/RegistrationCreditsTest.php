<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;

it('grants starter credits to newly registered user', function () {
    $user = User::factory()->create();

    $expected = (int) app(FeatureAccessService::class)->limit($user, 'monthly_credits');

    expect(app(CreditLedger::class)->balance($user))->toBe($expected);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => $expected,
        'balance_after' => $expected,
        'reason' => 'registration_grant',
    ]);
});

it('does not duplicate starter grant on user update', function () {
    $user = User::factory()->create();

    $user->name = 'Updated Name';
    $user->save();

    $expected = (int) app(FeatureAccessService::class)->limit($user, 'monthly_credits');

    expect(app(CreditLedger::class)->balance($user))->toBe($expected);

    expect(
        CreditTransaction::where('user_id', $user->id)
            ->where('reason', 'registration_grant')
            ->count()
    )->toBe(1);
});
