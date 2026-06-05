<?php

declare(strict_types=1);

use App\Jobs\CleanupExpiredFilesJob;
use Illuminate\Support\Facades\Bus;

it('dispatches the cleanup job when run without --sync', function () {
    Bus::fake();

    $this->artisan('files:cleanup-expired')
        ->expectsOutputToContain('Expired files cleanup queued')
        ->assertExitCode(0);

    Bus::assertDispatched(CleanupExpiredFilesJob::class);
});

it('runs cleanup synchronously with --sync flag and does not dispatch', function () {
    Bus::fake();

    $this->artisan('files:cleanup-expired --sync')
        ->expectsOutputToContain('Expired files cleanup completed')
        ->assertExitCode(0);

    Bus::assertNotDispatched(CleanupExpiredFilesJob::class);
});
