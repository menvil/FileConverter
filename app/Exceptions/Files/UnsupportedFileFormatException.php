<?php

namespace App\Exceptions\Files;

use DomainException;

final class UnsupportedFileFormatException extends DomainException
{
    public static function forFile(string $filename, ?string $mime = null): self
    {
        $hint = $mime !== null && $mime !== '' ? " ({$mime})" : '';

        return new self("Unsupported uploaded file format: {$filename}{$hint}");
    }
}
