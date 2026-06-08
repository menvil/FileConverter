<?php

declare(strict_types=1);

namespace App\Support\Converters\Exceptions;

use App\Exceptions\DomainExceptionContract;

final class InvalidConverterOptionsException extends \DomainException implements DomainExceptionContract
{
    /** @var array<string, string> */
    private array $errors = [];

    public function __construct(string $message, public readonly ?string $optionKey = null)
    {
        parent::__construct($message);

        if ($optionKey !== null) {
            $this->errors = [$optionKey => $message];
        }
    }

    /** @param array<string, string> $errors */
    public static function withErrors(array $errors): self
    {
        $first = array_key_first($errors);
        $e = new self(
            $first !== null ? ($errors[$first] ?? 'Invalid converter options.') : 'Invalid converter options.',
            $first,
        );
        $e->errors = $errors;

        return $e;
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

    public function code(): string
    {
        return 'invalid_options';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return ['errors' => $this->fieldErrors()];
    }

    /**
     * Field-level errors keyed by the offending option key.
     *
     * @return array<string, string>
     */
    public function fieldErrors(): array
    {
        if ($this->errors !== []) {
            return $this->errors;
        }

        if ($this->optionKey === null) {
            return [];
        }

        return [$this->optionKey => $this->getMessage()];
    }
}
