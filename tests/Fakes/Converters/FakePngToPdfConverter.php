<?php

namespace Tests\Fakes\Converters;

use App\Support\Converters\Contracts\Converter;

final class FakePngToPdfConverter implements Converter
{
    public function key(): string
    {
        return 'png_to_pdf';
    }

    public function sourceFormat(): string
    {
        return 'png';
    }

    public function targetFormat(): string
    {
        return 'pdf';
    }

    public function label(): string
    {
        return 'PDF';
    }

    public function description(): string
    {
        return 'Portable document for sharing';
    }

    public function optionsSchema(): array
    {
        return [];
    }

    public function validateOptions(array $options): array
    {
        return $options;
    }

    public function isRecommended(): bool
    {
        return false;
    }
}
