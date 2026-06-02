<?php

declare(strict_types=1);

namespace App\Exceptions\Storage;

use RuntimeException;

final class StorageLimitExceededException extends RuntimeException
{
    public static function make(int $limitMb, int $usedBytes, int $newFileBytes): self
    {
        $usedMb = round($usedBytes / 1024 / 1024, 1);
        $newMb = round($newFileBytes / 1024 / 1024, 1);

        return new self(
            "Storage limit of {$limitMb} MB exceeded. Used: {$usedMb} MB, file: {$newMb} MB."
        );
    }
}
