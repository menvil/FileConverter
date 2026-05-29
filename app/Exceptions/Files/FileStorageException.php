<?php

namespace App\Exceptions\Files;

use RuntimeException;
use Throwable;

final class FileStorageException extends RuntimeException
{
    public static function cannotStore(string $filename): self
    {
        return new self("Cannot store uploaded file: {$filename}");
    }

    public static function cannotCreateRecord(string $filename, ?Throwable $previous = null): self
    {
        return new self("Cannot persist file record for uploaded file: {$filename}", previous: $previous);
    }
}
