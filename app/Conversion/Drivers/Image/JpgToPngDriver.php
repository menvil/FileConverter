<?php

declare(strict_types=1);

namespace App\Conversion\Drivers\Image;

use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class JpgToPngDriver implements ConverterDriver
{
    public function key(): string
    {
        return 'jpg_to_png';
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        $manager = new ImageManager(new Driver);

        $sourcePath = Storage::disk('local')->path($context->sourceFile->stored_path);
        $image = $manager->decodePath($sourcePath);

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
