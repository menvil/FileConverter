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

final class JpgToWebpDriver implements ConverterDriver
{
    use ResolvesImageQuality;

    public function key(): string
    {
        return 'jpg_to_webp';
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        $storedPath = $context->sourceFile->stored_path;

        if (! Storage::disk('local')->exists($storedPath)) {
            throw new RuntimeException(
                "JpgToWebpDriver: source file not found at [{$storedPath}]."
            );
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->decodePath(Storage::disk('local')->path($storedPath));

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
}
