<?php

it('defines billing plans config for free pro and max', function () {
    $plans = config('billing_plans.plans');

    expect($plans)->toHaveKeys(['free', 'pro', 'max']);
});

it('defines stripe price ids for paid billing plans', function () {
    expect(config('billing_plans.plans.pro.stripe_price_id'))->not->toBeEmpty();
    expect(config('billing_plans.plans.max.stripe_price_id'))->not->toBeEmpty();
});
