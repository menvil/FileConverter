<?php

use App\Enums\Plan;
use App\Models\User;

it('assigns free plan to new users by default', function () {
    $user = User::factory()->create();

    expect($user->fresh()->plan)->toBe(Plan::Free);
});

it('persists explicit plan assignment', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    expect($user->fresh()->plan)->toBe(Plan::Pro);
});

it('exposes plan as enum on existing rows', function () {
    $user = User::factory()->create();

    expect($user->plan)->toBeInstanceOf(Plan::class);
    expect($user->plan->value)->toBe('free');
});
