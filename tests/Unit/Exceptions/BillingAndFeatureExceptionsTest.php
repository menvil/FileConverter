<?php

declare(strict_types=1);

use App\Exceptions\Billing\InsufficientCreditsException;
use App\Exceptions\DomainExceptionContract;
use App\Exceptions\Features\FeatureNotAvailableException;

it('creates insufficient credits exception with required and available values', function () {
    $exception = InsufficientCreditsException::forCost(
        required: 5,
        available: 2,
    );

    expect($exception->code())->toBe('insufficient_credits');
    expect($exception->details())->toMatchArray([
        'required' => 5,
        'available' => 2,
    ]);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});

it('creates feature not available exception with feature and plan', function () {
    $exception = FeatureNotAvailableException::forFeature('api_access', 'free');

    expect($exception->code())->toBe('feature_not_available');
    expect($exception->details())->toMatchArray([
        'feature' => 'api_access',
        'plan' => 'free',
    ]);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});
