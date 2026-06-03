<?php

declare(strict_types=1);

namespace App\Support\Api;

use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ApiOwnershipGuard
{
    public function ensureFileOwner(User $user, FileRecord $file): void
    {
        if ($file->user_id !== $user->id) {
            throw new AuthorizationException('You do not own this file.');
        }
    }

    public function ensureConversionOwner(User $user, ConversionJob $conversion): void
    {
        if ($conversion->user_id !== $user->id) {
            throw new AuthorizationException('You do not own this conversion.');
        }
    }
}
