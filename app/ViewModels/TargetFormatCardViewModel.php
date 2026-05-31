<?php

namespace App\ViewModels;

use App\Support\Converters\Contracts\Converter;
use App\Support\Converters\DTO\ConverterTarget;

final readonly class TargetFormatCardViewModel
{
    public function __construct(
        public string $targetFormat,
        public string $label,
        public string $description,
        public bool $recommended = false,
    ) {}

    public static function fromConverter(Converter $converter): self
    {
        return new self(
            targetFormat: $converter->targetFormat(),
            label: $converter->label(),
            description: $converter->description(),
            recommended: $converter->isRecommended(),
        );
    }

    public static function fromTarget(ConverterTarget $target): self
    {
        return new self(
            targetFormat: $target->format,
            label: $target->label,
            description: $target->description,
            recommended: $target->recommended,
        );
    }
}
