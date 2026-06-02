<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\CreditTransactionType;
use App\Exceptions\Billing\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;

it('refunds credits and records transaction', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);
    $startBalance = $ledger->balance($user);

    $ledger->grant($user, 100, 'test_grant');
    $ledger->spend($user, 30, 'test_spend');

    $transaction = $ledger->refund($user, 30, 'conversion_failed_refund');

    expect($ledger->balance($user))->toBe($startBalance + 100);
    expect($transaction->amount)->toBe(30);
    expect($transaction->balance_after)->toBe($startBalance + 100);
    expect($transaction->type)->toBe(CreditTransactionType::Refund);
    expect($transaction->reason)->toBe('conversion_failed_refund');
});

it('does not allow spending more credits than available', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);
    $startBalance = $ledger->balance($user);

    $ledger->grant($user, 10, 'test_grant');

    expect(fn () => $ledger->spend($user, $startBalance + 100, 'too_expensive'))
        ->toThrow(InsufficientCreditsException::class);

    expect($ledger->balance($user))->toBe($startBalance + 10);
    expect(
        CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', CreditTransactionType::Spend)
            ->count()
    )->toBe(0);
});

it('spends credits and records transaction', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);
    $startBalance = $ledger->balance($user);

    $ledger->grant($user, 100, 'test_grant');

    $transaction = $ledger->spend($user, 30, 'test_spend', [
        'operation' => 'png_to_jpg',
    ]);

    expect($ledger->balance($user))->toBe($startBalance + 70);
    expect($transaction->amount)->toBe(-30);
    expect($transaction->balance_after)->toBe($startBalance + 70);
    expect($transaction->type)->toBe(CreditTransactionType::Spend);
    expect($transaction->reason)->toBe('test_spend');
});

it('grants credits and records transaction', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);
    $startBalance = $ledger->balance($user);

    $transaction = $ledger->grant(
        user: $user,
        amount: 50,
        reason: 'test_grant',
        meta: ['source' => 'test']
    );

    expect($ledger->balance($user))->toBe($startBalance + 50);
    expect($transaction->amount)->toBe(50);
    expect($transaction->balance_after)->toBe($startBalance + 50);
    expect($transaction->type)->toBe(CreditTransactionType::Grant);
    expect($transaction->reason)->toBe('test_grant');
});
