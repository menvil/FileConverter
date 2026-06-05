<?php

declare(strict_types=1);

namespace App\Support\Converters\Exceptions;

use App\Exceptions\DomainExceptionContract;

final class UnsupportedFormatException extends \DomainException implements DomainExceptionContract
{
    private string $format = '';

    public static function forFormat(string $format): self
    {
        $e = new self("Unsupported file format: {$format}");
        $e->format = $format;

        return $e;
    }

    /** @deprecated Use forFormat() */
    public static function forInput(string $input): self
    {
        return self::forFormat($input);
    }

    public function code(): string
    {
        return 'unsupported_format';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return ['format' => $this->format];
    }
}
