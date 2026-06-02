<?php

// CONV-188: Test Max File Size Limit Resolution / CONV-194: Test Retention Days Resolution
// Uses User::make() (no DB) because FeatureAccessService only reads $user->plan.

use App\Enums\Plan;
use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;
use App\Services\FeatureAccess\PlanLimit;

it('resolves max file size limit for free plan', function () {
    $user = User::make(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->limit($user, 'max_file_size_mb'))->toBe(25);
});

it('resolves max file size limit for pro plan', function () {
    $user = User::make(['plan' => Plan::Pro]);

    expect(app(FeatureAccessService::class)->limit($user, 'max_file_size_mb'))->toBe(250);
});

it('resolves max file size limit for max plan', function () {
    $user = User::make(['plan' => Plan::Max]);

    expect(app(FeatureAccessService::class)->limit($user, 'max_file_size_mb'))->toBe(2000);
});

it('resolves all plan limits as dto', function () {
    $user = User::make(['plan' => Plan::Pro]);

    $limits = app(FeatureAccessService::class)->limits($user);

    expect($limits)->toBeInstanceOf(PlanLimit::class);
    expect($limits->maxFileSizeMb)->toBe(250);
    expect($limits->storageMb)->toBe(10000);
    expect($limits->retentionDays)->toBe(30);
    expect($limits->monthlyCredits)->toBe(1000);
});

// Retention days (CONV-194)

it('resolves retention days for free plan', function () {
    $user = User::make(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->limits($user)->retentionDays)->toBe(1);
});

it('resolves retention days for pro and max plans', function () {
    $pro = User::make(['plan' => Plan::Pro]);
    $max = User::make(['plan' => Plan::Max]);

    $service = app(FeatureAccessService::class);

    expect($service->limits($pro)->retentionDays)->toBe(30);
    expect($service->limits($max)->retentionDays)->toBe(90);
});
