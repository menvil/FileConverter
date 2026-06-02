<?php

declare(strict_types=1);

use App\Models\CreditAccount;
use App\Models\User;

it('creates a credit account for a user', function () {
    $user = User::factory()->create();

    $account = CreditAccount::factory()->create([
        'user_id' => $user->id,
        'balance' => 100,
    ]);

    expect($account->user->is($user))->toBeTrue();
    expect($account->balance)->toBe(100);
});

it('credit account belongs to user', function () {
    $user = User::factory()->create();
    $account = CreditAccount::factory()->create(['user_id' => $user->id]);

    expect($account->user)->toBeInstanceOf(User::class);
    expect($account->user->id)->toBe($user->id);
});

it('user has one credit account', function () {
    $user = User::factory()->create();
    CreditAccount::factory()->create(['user_id' => $user->id]);

    expect($user->creditAccount)->toBeInstanceOf(CreditAccount::class);
});

it('balance defaults to zero', function () {
    $user = User::factory()->create();
    $account = CreditAccount::factory()->create(['user_id' => $user->id]);

    expect($account->balance)->toBe(0);
});
