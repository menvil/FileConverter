<?php

namespace Tests\Fakes\Converters;

use App\Support\Converters\Contracts\Converter;

final class FakePngToJpgConverter implements Converter
{
    public function key(): string
    {
        return 'png_to_jpg';
    }

    public function sourceFormat(): string
    {
        return 'png';
    }

    public function targetFormat(): string
    {
        return 'jpg';
    }

    public function label(): string
    {
        return 'JPG';
    }

    public function description(): string
    {
        return 'Best for photos and sharing';
    }

    public function optionsSchema(): array
    {
        return [];
    }

    public function validateOptions(array $options): array
    {
        return $options;
    }
}
