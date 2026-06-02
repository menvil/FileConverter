<?php

use App\Billing\BillingPlanRepository;
use App\Billing\Exceptions\UnknownBillingPlanException;

it('returns paid billing plan by key', function () {
    $plan = app(BillingPlanRepository::class)->findOrFail('pro');

    expect($plan->key)->toBe('pro');
    expect($plan->isPaid)->toBeTrue();
});

it('rejects unknown billing plan key', function () {
    app(BillingPlanRepository::class)->findOrFail('unknown');
})->throws(UnknownBillingPlanException::class);

it('returns all billing plans as dtos', function () {
    $plans = app(BillingPlanRepository::class)->all();

    expect($plans)->toHaveCount(3);
});

it('returns only paid billing plans', function () {
    $plans = app(BillingPlanRepository::class)->paid();

    expect($plans)->toHaveCount(2);
    foreach ($plans as $plan) {
        expect($plan->isPaid)->toBeTrue();
    }
});
