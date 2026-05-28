<?php

namespace App\Support\Converters\Exceptions;

use DomainException;

final class InvalidOptionsSchemaException extends DomainException
{
    public static function becauseFieldIsInvalid(): self
    {
        return new self('Options schema field must be an array.');
    }

    public static function becauseKeyIsMissing(): self
    {
        return new self('Options schema field is missing the "key" attribute.');
    }

    public static function becauseKeyIsDuplicated(string $key): self
    {
        return new self("Options schema contains duplicate field key: {$key}.");
    }

    public static function becauseTypeIsUnsupported(string $type): self
    {
        return new self("Options schema field type is not supported: {$type}.");
    }

    public static function becauseLabelIsMissing(string $key): self
    {
        return new self("Options schema field [{$key}] is missing the \"label\" attribute.");
    }

    public static function becauseOptionsAreRequired(string $key): self
    {
        return new self("Options schema field [{$key}] requires a non-empty \"options\" list.");
    }

    public static function becauseOptionItemIsInvalid(string $key): self
    {
        return new self("Options schema field [{$key}] contains an invalid option entry.");
    }
}
