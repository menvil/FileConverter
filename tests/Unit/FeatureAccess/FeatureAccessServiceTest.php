<?php

// CONV-182: Skeleton / CONV-183+184: Free / CONV-185+186: Pro and Max
// Uses User::make() (no DB) because FeatureAccessService only reads $user->plan.

use App\Enums\Plan;
use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;

it('has feature access service with allows and limit methods', function () {
    $service = app(FeatureAccessService::class);

    expect(method_exists($service, 'allows'))->toBeTrue();
    expect(method_exists($service, 'limit'))->toBeTrue();
});

// Free plan feature access

it('does not allow api access for free plan', function () {
    $user = User::make(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->allows($user, 'api_access'))->toBeFalse();
});

it('does not allow batch conversion for free plan', function () {
    $user = User::make(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->allows($user, 'batch_conversion'))->toBeFalse();
});

it('returns false for unknown feature', function () {
    $user = User::make(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->allows($user, 'non_existent_feature'))->toBeFalse();
});

// Pro plan feature access

it('allows api access for pro plan', function () {
    $user = User::make(['plan' => Plan::Pro]);

    expect(app(FeatureAccessService::class)->allows($user, 'api_access'))->toBeTrue();
});

it('allows batch conversion for pro plan', function () {
    $user = User::make(['plan' => Plan::Pro]);

    expect(app(FeatureAccessService::class)->allows($user, 'batch_conversion'))->toBeTrue();
});

it('does not allow priority queue for pro plan', function () {
    $user = User::make(['plan' => Plan::Pro]);

    expect(app(FeatureAccessService::class)->allows($user, 'priority_queue'))->toBeFalse();
});

// Max plan feature access

it('allows api access for max plan', function () {
    $user = User::make(['plan' => Plan::Max]);

    expect(app(FeatureAccessService::class)->allows($user, 'api_access'))->toBeTrue();
});

it('allows priority queue only for max plan', function () {
    $pro = User::make(['plan' => Plan::Pro]);
    $max = User::make(['plan' => Plan::Max]);

    $service = app(FeatureAccessService::class);

    expect($service->allows($pro, 'priority_queue'))->toBeFalse();
    expect($service->allows($max, 'priority_queue'))->toBeTrue();
});
