<?php

// CONV-181: Create PlanFeatures Config

it('defines feature access config for all plans', function () {
    $config = config('feature-access.plans');

    expect($config)->toHaveKeys(['free', 'pro', 'max']);

    foreach (['free', 'pro', 'max'] as $plan) {
        expect($config[$plan])->toHaveKeys(['features', 'limits']);
    }
});

it('free plan has api_access disabled', function () {
    expect(config('feature-access.plans.free.features.api_access'))->toBeFalse();
});

it('pro plan has api_access enabled', function () {
    expect(config('feature-access.plans.pro.features.api_access'))->toBeTrue();
});

it('max plan has api_access enabled', function () {
    expect(config('feature-access.plans.max.features.api_access'))->toBeTrue();
});

it('each plan defines all required limits', function () {
    foreach (['free', 'pro', 'max'] as $plan) {
        expect(config("feature-access.plans.{$plan}.limits"))->toHaveKeys([
            'max_file_size_mb',
            'storage_mb',
            'retention_days',
            'monthly_credits',
        ]);
    }
});
