<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;

final class StorageUsageService
{
    public function usedBytes(User $user): int
    {
        return (int) FileRecord::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [FileStatus::Expired->value, FileStatus::Deleted->value])
            ->sum('size_bytes');
    }

    public function usedMegabytes(User $user): float
    {
        return round($this->usedBytes($user) / 1024 / 1024, 2);
    }
}
