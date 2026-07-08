<?php

namespace App\Support\Converters\Image;

use App\Support\Converters\Contracts\Converter;
use App\Support\Converters\OptionsValidator;

final class PngToJpgConverter implements Converter
{
    public function __construct(
        private readonly OptionsValidator $optionsValidator,
    ) {}

    public function key(): string
    {
        return 'png:jpg';
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
        return [
            [
                'key' => 'quality',
                'type' => 'segmented',
                'label' => 'Quality',
                'default' => 'high',
                'options' => [
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                    ['value' => 'best', 'label' => 'Best'],
                ],
            ],
            [
                'key' => 'background_color',
                'type' => 'color',
                'label' => 'Background color',
                'default' => '#ffffff',
            ],
        ];
    }

    public function validateOptions(array $options): array
    {
        return $this->optionsValidator->validate($this->optionsSchema(), $options);
    }

    public function isRecommended(): bool
    {
        return true;
    }
}
