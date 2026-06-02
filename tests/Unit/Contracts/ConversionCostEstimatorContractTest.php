<?php

declare(strict_types=1);

use App\Contracts\Billing\ConversionCostEstimator;

it('can resolve conversion cost estimator contract', function () {
    expect(interface_exists(ConversionCostEstimator::class))->toBeTrue();
});
