<?php

namespace App\Support\Converters;

use App\Enums\FileFormat;
use App\Support\Converters\Contracts\Converter;
use App\Support\Converters\DTO\ConverterTarget;
use App\Support\Converters\Exceptions\UnsupportedFormatException;

final class ConverterRegistry
{
    /** @param list<Converter> $converters */
    public function __construct(
        private readonly array $converters = [],
    ) {}

    /** @return list<Converter> */
    public function all(): array
    {
        return array_values($this->converters);
    }

    /** @return list<Converter> */
    public function forSource(string $source): array
    {
        try {
            $source = FileFormat::normalize($source);
        } catch (UnsupportedFormatException) {
            return [];
        }

        return array_values(array_filter(
            $this->converters,
            fn (Converter $converter): bool => $converter->sourceFormat() === $source,
        ));
    }

    public function find(string $source, string $target): ?Converter
    {
        try {
            $source = FileFormat::normalize($source);
            $target = FileFormat::normalize($target);
        } catch (UnsupportedFormatException) {
            return null;
        }

        foreach ($this->converters as $converter) {
            if ($converter->sourceFormat() === $source && $converter->targetFormat() === $target) {
                return $converter;
            }
        }

        return null;
    }

    /** @return list<ConverterTarget> */
    public function targetsFor(string $source): array
    {
        return array_map(
            fn (Converter $converter): ConverterTarget => new ConverterTarget(
                format: $converter->targetFormat(),
                label: $converter->label(),
                description: $converter->description(),
                converterKey: $converter->key(),
                recommended: $converter->isRecommended(),
            ),
            $this->forSource($source),
        );
    }
}
