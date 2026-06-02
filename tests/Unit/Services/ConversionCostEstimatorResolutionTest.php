<?php

declare(strict_types=1);

use App\Contracts\Billing\ConversionCostEstimator;
use App\Services\Billing\ConfigDrivenConversionCostEstimator;

it('resolves conversion cost estimator implementation from container', function () {
    $estimator = app(ConversionCostEstimator::class);

    expect($estimator)->toBeInstanceOf(ConfigDrivenConversionCostEstimator::class);
});
