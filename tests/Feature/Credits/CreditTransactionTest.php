<?php

declare(strict_types=1);

use App\Models\CreditTransaction;
use App\Models\User;

it('stores credit transaction metadata as array', function () {
    $user = User::factory()->create();

    $transaction = CreditTransaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 50,
        'balance_after' => 50,
        'type' => 'grant',
        'reason' => 'registration_grant',
        'metadata_json' => ['plan' => 'free'],
    ]);

    expect($transaction->user->is($user))->toBeTrue();
    expect($transaction->metadata_json)->toBeArray();
    expect($transaction->metadata_json['plan'])->toBe('free');
});

it('credit transaction belongs to user', function () {
    $user = User::factory()->create();
    $transaction = CreditTransaction::factory()->create(['user_id' => $user->id]);

    expect($transaction->user)->toBeInstanceOf(User::class);
    expect($transaction->user->id)->toBe($user->id);
});

it('amount is cast to integer', function () {
    $transaction = CreditTransaction::factory()->create(['amount' => 10]);

    expect($transaction->amount)->toBeInt();
});

it('balance_after is cast to integer', function () {
    $transaction = CreditTransaction::factory()->create(['balance_after' => 10]);

    expect($transaction->balance_after)->toBeInt();
});

it('metadata_json is null when not provided', function () {
    $transaction = CreditTransaction::factory()->create(['metadata_json' => null]);

    expect($transaction->metadata_json)->toBeNull();
});
