<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Exceptions\Files\InvalidRetentionPolicyException;
use App\Models\User;
use App\Services\Files\FileRetentionPolicy;
use Carbon\Carbon;

it('calculates expiration date from user retention limit', function () {
    Carbon::setTestNow('2026-06-01 10:00:00');

    $user = User::make(['plan' => Plan::Free]); // free plan: 1 retention day

    $policy = app(FileRetentionPolicy::class);

    expect($policy->expiresAtFor($user))->toEqual(now()->addDay());
});

it('calculates expiration from a custom base time', function () {
    $user = User::make(['plan' => Plan::Free]); // 1 day

    $policy = app(FileRetentionPolicy::class);

    $from = Carbon::parse('2026-06-01 10:00:00');

    expect($policy->expiresAtFor($user, $from))->toEqual(Carbon::parse('2026-06-02 10:00:00'));
});

it('calculates expiration for pro plan', function () {
    Carbon::setTestNow('2026-06-01 10:00:00');

    $user = User::make(['plan' => Plan::Pro]); // pro plan: 30 retention days

    $policy = app(FileRetentionPolicy::class);

    expect($policy->expiresAtFor($user))->toEqual(now()->addDays(30));
});

it('throws for zero retention days', function () {
    config(['feature-access.plans.free.limits.retention_days' => 0]);

    $user = User::make(['plan' => Plan::Free]);
    $policy = app(FileRetentionPolicy::class);

    expect(fn () => $policy->expiresAtFor($user))
        ->toThrow(InvalidRetentionPolicyException::class);
});

it('throws for negative retention days', function () {
    config(['feature-access.plans.free.limits.retention_days' => -5]);

    $user = User::make(['plan' => Plan::Free]);
    $policy = app(FileRetentionPolicy::class);

    expect(fn () => $policy->expiresAtFor($user))
        ->toThrow(InvalidRetentionPolicyException::class);
});
