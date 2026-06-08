<?php

declare(strict_types=1);

namespace App\Support\Conversions\Exceptions;

use App\Exceptions\DomainException;
use Throwable;

final class UnsupportedConversionException extends DomainException
{
    private string $sourceFormat = '';

    private string $targetFormat = '';

    public static function forPair(string $sourceFormat, string $targetFormat, ?Throwable $previous = null): self
    {
        $e = new self(
            "No converter is available to convert [{$sourceFormat}] to [{$targetFormat}].",
            previous: $previous,
        );
        $e->sourceFormat = $sourceFormat;
        $e->targetFormat = $targetFormat;

        return $e;
    }

    public function code(): string
    {
        return 'unsupported_conversion';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return [
            'source_format' => $this->sourceFormat,
            'target_format' => $this->targetFormat,
        ];
    }
}
