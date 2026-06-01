<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image;

use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class PngToJpgDriver implements ConverterDriver
{
    public function key(): string
    {
        return 'png_to_jpg';
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        $manager = new ImageManager(new Driver);

        $sourcePath = Storage::disk('local')->path($context->sourceFile->stored_path);
        $image = $manager->decodePath($sourcePath);

        $background = $context->options['background'] ?? '#ffffff';
        $quality = $this->resolveQuality($context->options['quality'] ?? 'high');

        $image->fillTransparentAreas($background);

        $encoded = $image->encodeUsingFileExtension('jpg', quality: $quality);

        $outputPath = $context->outputDirectory.'/result.jpg';
        Storage::disk('local')->put($outputPath, (string) $encoded);

        return new ConversionResult(
            path: $outputPath,
            originalName: 'result.jpg',
            mimeType: 'image/jpeg',
            extension: 'jpg',
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
