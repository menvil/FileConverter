<?php

namespace App\Support\Converters\DTO;

final readonly class ConverterTarget
{
    public function __construct(
        public string $format,
        public string $label,
        public string $description,
        public string $converterKey,
        public bool $recommended = false,
    ) {}

    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'label' => $this->label,
            'description' => $this->description,
            'converter_key' => $this->converterKey,
            'recommended' => $this->recommended,
        ];
    }
}
