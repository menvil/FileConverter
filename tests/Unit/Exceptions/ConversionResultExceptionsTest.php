<?php

declare(strict_types=1);

use App\Exceptions\Conversions\ConversionFailedException;
use App\Exceptions\Conversions\ConversionResultExpiredException;
use App\Exceptions\DomainExceptionContract;

it('creates conversion failed exception with job context', function () {
    $exception = ConversionFailedException::forJob(
        jobId: 'conv_123',
        reason: 'Driver failed.',
    );

    expect($exception->code())->toBe('conversion_failed');
    expect($exception->details())->toMatchArray([
        'conversion_job_id' => 'conv_123',
    ]);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});

it('creates result expired exception with conversion id', function () {
    $exception = ConversionResultExpiredException::forConversion('conv_123');

    expect($exception->code())->toBe('result_expired');
    expect($exception->details())->toMatchArray([
        'conversion_job_id' => 'conv_123',
    ]);
    expect($exception)->toBeInstanceOf(DomainExceptionContract::class);
});
