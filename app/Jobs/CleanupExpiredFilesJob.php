<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\FileStatus;
use App\Models\FileRecord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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
                        $exists = Storage::disk('local')->exists($file->stored_path);

                        if ($exists) {
                            $deleted = Storage::disk('local')->delete($file->stored_path);

                            if (! $deleted) {
                                Log::warning('CleanupExpiredFilesJob: failed to delete file', [
                                    'file_id' => $file->id,
                                    'path' => $file->stored_path,
                                ]);

                                continue;
                            }
                        }
                    }

                    $file->forceFill(['status' => FileStatus::Expired])->save();
                }
            });
    }
}
