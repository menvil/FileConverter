<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CleanupExpiredFilesJob;
use Illuminate\Console\Command;

final class CleanupExpiredFilesCommand extends Command
{
    protected $signature = 'files:cleanup-expired {--sync : Run cleanup synchronously instead of dispatching a queue job}';

    protected $description = 'Delete expired physical files and mark their records as expired';

    public function handle(): int
    {
        if ($this->option('sync')) {
            app(CleanupExpiredFilesJob::class)->handle();
            $this->info('Expired files cleanup completed.');
        } else {
            CleanupExpiredFilesJob::dispatch();
            $this->info('Expired files cleanup queued.');
        }

        return self::SUCCESS;
    }
}
