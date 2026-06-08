<?php

declare(strict_types=1);

namespace App\Exceptions\Storage;

use App\Exceptions\DomainExceptionContract;

final class StorageLimitExceededException extends \RuntimeException implements DomainExceptionContract
{
    private int $usedBytes = 0;

    private int $maxBytes = 0;

    public static function make(int $limitMb, int $usedBytes, int $newFileBytes): self
    {
        $usedMb = round($usedBytes / 1024 / 1024, 1);
        $newMb = round($newFileBytes / 1024 / 1024, 1);

        $e = new self(
            "Storage limit of {$limitMb} MB exceeded. Used: {$usedMb} MB, file: {$newMb} MB."
        );
        $e->usedBytes = $usedBytes;
        $e->maxBytes = $limitMb * 1024 * 1024;

        return $e;
    }

    public function code(): string
    {
        return 'storage_limit_exceeded';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return [
            'used_bytes' => $this->usedBytes,
            'max_bytes' => $this->maxBytes,
        ];
    }
}
