<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image;

use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

final class JpgToPngDriver implements ConverterDriver
{
    public function key(): string
    {
        return 'jpg_to_png';
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        $storedPath = $context->sourceFile->stored_path;

        if (! Storage::disk('local')->exists($storedPath)) {
            throw new RuntimeException(
                "JpgToPngDriver: source file not found at [{$storedPath}]."
            );
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->decodePath(Storage::disk('local')->path($storedPath));

        $encoded = $image->encodeUsingFileExtension('png');

        $outputPath = $context->outputDirectory.'/result.png';
        Storage::disk('local')->put($outputPath, (string) $encoded);

        return new ConversionResult(
            path: $outputPath,
            originalName: 'result.png',
            mimeType: 'image/png',
            extension: 'png',
            sizeBytes: Storage::disk('local')->size($outputPath),
        );
    }
}
