<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\CreditTransactionType;
use App\Models\User;

it('spends credits and records transaction', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);

    $ledger->grant($user, 100, 'test_grant');

    $transaction = $ledger->spend($user, 30, 'test_spend', [
        'operation' => 'png_to_jpg',
    ]);

    expect($ledger->balance($user))->toBe(70);
    expect($transaction->amount)->toBe(-30);
    expect($transaction->balance_after)->toBe(70);
    expect($transaction->type)->toBe(CreditTransactionType::Spend);
    expect($transaction->reason)->toBe('test_spend');
});

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
