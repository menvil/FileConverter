<?php

declare(strict_types=1);

use App\Jobs\ProcessConversionJob;

it('has process conversion job with handle method', function () {
    $job = new ProcessConversionJob(conversionJobId: 123);

    expect(method_exists($job, 'handle'))->toBeTrue();
    expect($job->conversionJobId)->toBe(123);
});
