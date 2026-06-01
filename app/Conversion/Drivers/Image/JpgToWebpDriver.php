<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image;

use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class JpgToWebpDriver implements ConverterDriver
{
    public function key(): string
    {
        return 'jpg_to_webp';
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        $manager = new ImageManager(new Driver);

        $sourcePath = Storage::disk('local')->path($context->sourceFile->stored_path);
        $image = $manager->decodePath($sourcePath);

        $quality = $this->resolveQuality($context->options['quality'] ?? 'high');

        $encoded = $image->encodeUsingFileExtension('webp', quality: $quality);

        $outputPath = $context->outputDirectory.'/result.webp';
        Storage::disk('local')->put($outputPath, (string) $encoded);

        return new ConversionResult(
            path: $outputPath,
            originalName: 'result.webp',
            mimeType: 'image/webp',
            extension: 'webp',
            sizeBytes: Storage::disk('local')->size($outputPath),
        );
    }

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
