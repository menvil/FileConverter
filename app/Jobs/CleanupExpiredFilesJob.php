<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\FileStatus;
use App\Models\FileRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

final class CleanupExpiredFilesJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        FileRecord::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotIn('status', [FileStatus::Expired, FileStatus::Deleted])
            ->chunkById(100, function ($files): void {
                foreach ($files as $file) {
                    if ($file->stored_path) {
                        Storage::disk(config('filesystems.default'))
                            ->delete($file->stored_path);
                    }

                    $file->forceFill(['status' => FileStatus::Expired])->save();
                }
            });
    }
}
