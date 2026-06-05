<?php

declare(strict_types=1);

namespace App\Exceptions\Files;

use App\Exceptions\DomainException;

final class FileTooLargeException extends DomainException
{
    private int $actualBytes = 0;

    private int $maxBytes = 0;

    public static function forLimit(int $actualBytes, int $maxBytes): self
    {
        $actualMb = round($actualBytes / 1024 / 1024, 1);
        $maxMb = round($maxBytes / 1024 / 1024, 1);

        $e = new self("File size {$actualMb} MB exceeds the limit of {$maxMb} MB.");
        $e->actualBytes = $actualBytes;
        $e->maxBytes = $maxBytes;

        return $e;
    }

    public function code(): string
    {
        return 'file_too_large';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return [
            'actual_bytes' => $this->actualBytes,
            'max_bytes' => $this->maxBytes,
        ];
    }
}
