<?php

namespace App\Support\Converters\Image;

use App\Support\Converters\Contracts\Converter;
use App\Support\Converters\OptionsValidator;

final class JpgToWebpConverter implements Converter
{
    public function __construct(
        private readonly OptionsValidator $optionsValidator,
    ) {}

    public function key(): string
    {
        return 'jpg:webp';
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
        return 'Smaller modern image for websites';
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
