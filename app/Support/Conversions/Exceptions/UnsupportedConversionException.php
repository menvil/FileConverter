<?php

declare(strict_types=1);

namespace App\Support\Conversions\Exceptions;

use RuntimeException;
use Throwable;

final class UnsupportedConversionException extends RuntimeException
{
    public static function forPair(string $sourceFormat, string $targetFormat, ?Throwable $previous = null): self
    {
        return new self(
            "No converter is available to convert [{$sourceFormat}] to [{$targetFormat}].",
            previous: $previous,
        );
    }
}
