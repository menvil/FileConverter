<?php

declare(strict_types=1);

use App\Models\CreditAccount;
use App\Models\User;

it('creates a credit account for a user', function () {
    $user = User::factory()->create();

    $account = $user->creditAccount;
    $account->balance = 100;
    $account->save();

    expect($account->fresh()->user->is($user))->toBeTrue();
    expect($account->fresh()->balance)->toBe(100);
});

it('credit account belongs to user', function () {
    $user = User::factory()->create();
    $account = $user->creditAccount;

    expect($account->user)->toBeInstanceOf(User::class);
    expect($account->user->id)->toBe($user->id);
});

it('user has one credit account', function () {
    $user = User::factory()->create();

    expect($user->creditAccount)->toBeInstanceOf(CreditAccount::class);
});

it('balance defaults to zero', function () {
    $user = User::factory()->create();

    expect($user->creditAccount->balance)->toBe(0);
});
