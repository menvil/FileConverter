<?php

namespace Tests\Fakes\Converters;

use App\Support\Converters\Contracts\Converter;

final class FakeJpgToWebpConverter implements Converter
{
    public function key(): string
    {
        return 'jpg_to_webp';
    }

    public function sourceFormat(): string
    {
        return 'jpg';
    }

    public function targetFormat(): string
    {
        return 'webp';
    }

    public function label(): string
    {
        return 'WEBP';
    }

    public function description(): string
    {
        return 'Modern web image format';
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
