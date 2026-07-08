<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image;

use App\Conversion\Drivers\Image\Concerns\ResolvesImageQuality;
use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

final class PngToJpgDriver implements ConverterDriver
{
    use ResolvesImageQuality;

    public function key(): string
    {
        return 'png_to_jpg';
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        $storedPath = $context->sourceFile->stored_path;

        if (! Storage::disk('local')->exists($storedPath)) {
            throw new RuntimeException(
                "PngToJpgDriver: source file not found at [{$storedPath}]."
            );
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->decodePath(Storage::disk('local')->path($storedPath));

        $background = $context->options['background_color'] ?? '#ffffff';
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
}
