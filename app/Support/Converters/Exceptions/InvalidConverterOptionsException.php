<?php

namespace App\Support\Converters\Exceptions;

use DomainException;

final class InvalidConverterOptionsException extends DomainException
{
    public function __construct(string $message, public readonly ?string $optionKey = null)
    {
        parent::__construct($message);
    }

    public static function becauseOptionIsUnknown(string $key): self
    {
        return new self("Unknown converter option: {$key}.", $key);
    }

    public static function becauseOptionIsRequired(string $key): self
    {
        return new self("Converter option [{$key}] is required.", $key);
    }

    public static function becauseValueIsNotAllowed(string $key): self
    {
        return new self("Converter option [{$key}] has an invalid value.", $key);
    }

    /**
     * Field-level errors keyed by the offending option key.
     *
     * @return array<string, string>
     */
    public function fieldErrors(): array
    {
        if ($this->optionKey === null) {
            return [];
        }

        return [$this->optionKey => $this->getMessage()];
    }
}
