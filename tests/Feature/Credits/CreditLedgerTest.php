<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\CreditTransactionType;
use App\Models\User;

it('grants credits and records transaction', function () {
    $user = User::factory()->create();

    $transaction = app(CreditLedger::class)->grant(
        user: $user,
        amount: 50,
        reason: 'test_grant',
        meta: ['source' => 'test']
    );

    expect(app(CreditLedger::class)->balance($user))->toBe(50);
    expect($transaction->amount)->toBe(50);
    expect($transaction->balance_after)->toBe(50);
    expect($transaction->type)->toBe(CreditTransactionType::Grant);
    expect($transaction->reason)->toBe('test_grant');
});
