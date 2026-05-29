<?php

namespace App\Support\Converters\Image;

use App\Support\Converters\Contracts\Converter;
use App\Support\Converters\OptionsValidator;

final class JpgToPngConverter implements Converter
{
    public function __construct(
        private readonly OptionsValidator $optionsValidator,
    ) {}

    public function key(): string
    {
        return 'jpg:png';
    }

    public function sourceFormat(): string
    {
        return 'jpg';
    }

    public function targetFormat(): string
    {
        return 'png';
    }

    public function label(): string
    {
        return 'PNG';
    }

    public function description(): string
    {
        return 'Convert photo to lossless PNG image';
    }

    public function optionsSchema(): array
    {
        return [
            [
                'key' => 'resize',
                'type' => 'select',
                'label' => 'Resize',
                'default' => 'original',
                'options' => [
                    ['value' => 'original', 'label' => 'Original'],
                    ['value' => '1920', 'label' => '1920 px'],
                    ['value' => '1280', 'label' => '1280 px'],
                    ['value' => 'custom', 'label' => 'Custom'],
                ],
            ],
            [
                'key' => 'remove_metadata',
                'type' => 'toggle',
                'label' => 'Remove metadata',
                'default' => true,
            ],
        ];
    }

    public function validateOptions(array $options): array
    {
        return $this->optionsValidator->validate($this->optionsSchema(), $options);
    }
}
