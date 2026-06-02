<?php

use App\Billing\BillingPlanDto;

it('creates billing plan dto from config data', function () {
    $dto = new BillingPlanDto(
        key: 'pro',
        label: 'Pro',
        stripePriceId: 'price_test_pro',
        monthlyCredits: 1000,
        isPaid: true,
    );

    expect($dto->key)->toBe('pro');
    expect($dto->monthlyCredits)->toBe(1000);
    expect($dto->isPaid)->toBeTrue();
});
