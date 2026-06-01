<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image\Concerns;

trait ResolvesImageQuality
{
    private function resolveQuality(mixed $quality): int
    {
        return match ($quality) {
            'low' => 60,
            'medium' => 75,
            'high' => 90,
            'max' => 100,
            default => 85,
        };
    }
}
