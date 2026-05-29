<?php

namespace App\Exceptions\Files;

use RuntimeException;

final class FileMetadataException extends RuntimeException
{
    public static function cannotReadImage(string $filename): self
    {
        return new self("Cannot read image metadata for file: {$filename}");
    }
}
