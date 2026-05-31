<?php

declare(strict_types=1);

namespace App\Support\Conversions\Exceptions;

use RuntimeException;

final class MissingConverterDriverException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("No converter driver is registered for key [{$key}].");
    }
}
