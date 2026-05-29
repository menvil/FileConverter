<?php

namespace App\Support\Converters\Image;

use App\Support\Converters\Contracts\Converter;
use App\Support\Converters\OptionsValidator;

final class PngToPdfConverter implements Converter
{
    public function __construct(
        private readonly OptionsValidator $optionsValidator,
    ) {}

    public function key(): string
    {
        return 'png:pdf';
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
        return 'Create a PDF document from image';
    }

    public function optionsSchema(): array
    {
        return [
            [
                'key' => 'page_size',
                'type' => 'segmented',
                'label' => 'Page size',
                'default' => 'auto',
                'options' => [
                    ['value' => 'auto', 'label' => 'Auto'],
                    ['value' => 'a4', 'label' => 'A4'],
                    ['value' => 'letter', 'label' => 'Letter'],
                ],
            ],
            [
                'key' => 'orientation',
                'type' => 'segmented',
                'label' => 'Orientation',
                'default' => 'auto',
                'options' => [
                    ['value' => 'auto', 'label' => 'Auto'],
                    ['value' => 'portrait', 'label' => 'Portrait'],
                    ['value' => 'landscape', 'label' => 'Landscape'],
                ],
            ],
            [
                'key' => 'margin',
                'type' => 'segmented',
                'label' => 'Margin',
                'default' => 'small',
                'options' => [
                    ['value' => 'none', 'label' => 'None'],
                    ['value' => 'small', 'label' => 'Small'],
                    ['value' => 'medium', 'label' => 'Medium'],
                ],
            ],
            [
                'key' => 'fit_mode',
                'type' => 'segmented',
                'label' => 'Fit mode',
                'default' => 'contain',
                'options' => [
                    ['value' => 'contain', 'label' => 'Contain'],
                    ['value' => 'cover', 'label' => 'Cover'],
                    ['value' => 'original', 'label' => 'Original'],
                ],
            ],
            [
                'key' => 'compression',
                'type' => 'segmented',
                'label' => 'Compression',
                'default' => 'balanced',
                'options' => [
                    ['value' => 'none', 'label' => 'None'],
                    ['value' => 'balanced', 'label' => 'Balanced'],
                    ['value' => 'small', 'label' => 'Small'],
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
        return false;
    }
}
