<?php

declare(strict_types=1);

use App\Jobs\CleanupExpiredFilesJob;
use Illuminate\Contracts\Queue\ShouldQueue;

it('has cleanup expired files job handle method', function () {
    $job = app(CleanupExpiredFilesJob::class);

    expect(method_exists($job, 'handle'))->toBeTrue();
});

it('implements ShouldQueue interface', function () {
    $job = app(CleanupExpiredFilesJob::class);

    expect($job)->toBeInstanceOf(ShouldQueue::class);
});
