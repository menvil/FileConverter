<?php

declare(strict_types=1);

use App\Conversion\Drivers\Image\JpgToPdfDriver;
use App\Conversion\Drivers\Image\JpgToPngDriver;
use App\Conversion\Drivers\Image\JpgToWebpDriver;
use App\Conversion\Drivers\Image\PngToJpgDriver;
use App\Conversion\Drivers\Image\PngToPdfDriver;
use App\Conversion\Drivers\Image\PngToWebpDriver;
use App\Support\Conversions\ConverterDriverRegistry;

it('resolves real image drivers for mvp converters', function () {
    $registry = app(ConverterDriverRegistry::class);

    expect($registry->find('png_to_jpg'))->toBeInstanceOf(PngToJpgDriver::class);
    expect($registry->find('jpg_to_png'))->toBeInstanceOf(JpgToPngDriver::class);
    expect($registry->find('png_to_webp'))->toBeInstanceOf(PngToWebpDriver::class);
    expect($registry->find('jpg_to_webp'))->toBeInstanceOf(JpgToWebpDriver::class);
    expect($registry->find('png_to_pdf'))->toBeInstanceOf(PngToPdfDriver::class);
    expect($registry->find('jpg_to_pdf'))->toBeInstanceOf(JpgToPdfDriver::class);
});
