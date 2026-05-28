<?php

namespace App\Support\Converters\Exceptions;

use DomainException;

final class UnsupportedFormatException extends DomainException
{
    public static function forInput(string $input): self
    {
        return new self("Unsupported file format: {$input}");
    }
}
