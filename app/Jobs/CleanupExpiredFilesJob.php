<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CleanupExpiredFilesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Implemented in CONV-338 (physical deletion) and CONV-340 (record marking).
    }
}
