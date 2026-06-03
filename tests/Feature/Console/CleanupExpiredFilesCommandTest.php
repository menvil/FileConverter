<?php

declare(strict_types=1);

it('runs expired files cleanup command successfully', function () {
    $this->artisan('files:cleanup-expired')
        ->expectsOutputToContain('Expired files cleanup completed')
        ->assertExitCode(0);
});

it('runs cleanup synchronously with --sync flag', function () {
    $this->artisan('files:cleanup-expired --sync')
        ->expectsOutputToContain('Expired files cleanup completed')
        ->assertExitCode(0);
});
