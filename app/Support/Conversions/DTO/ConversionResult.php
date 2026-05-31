<?php

declare(strict_types=1);

namespace App\Support\Conversions\DTO;

final class ConversionResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $path,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly string $extension,
        public readonly int $sizeBytes,
        public readonly array $metadata = [],
    ) {}
}
