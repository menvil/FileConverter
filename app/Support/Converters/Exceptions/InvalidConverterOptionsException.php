<?php

namespace App\Support\Converters\Exceptions;

use DomainException;

final class InvalidConverterOptionsException extends DomainException
{
    public static function becauseOptionIsUnknown(string $key): self
    {
        return new self("Unknown converter option: {$key}.");
    }

    public static function becauseOptionIsRequired(string $key): self
    {
        return new self("Converter option [{$key}] is required.");
    }

    public static function becauseValueIsNotAllowed(string $key): self
    {
        return new self("Converter option [{$key}] has an invalid value.");
    }
}
