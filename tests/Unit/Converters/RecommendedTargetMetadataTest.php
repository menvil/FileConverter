<?php

use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\Image\JpgToPdfConverter;
use App\Support\Converters\Image\JpgToPngConverter;
use App\Support\Converters\Image\JpgToWebpConverter;
use App\Support\Converters\Image\PngToJpgConverter;
use App\Support\Converters\Image\PngToPdfConverter;
use App\Support\Converters\Image\PngToWebpConverter;

function mvpRegistry(): ConverterRegistry
{
    return new ConverterRegistry([
        app(PngToJpgConverter::class),
        app(PngToWebpConverter::class),
        app(PngToPdfConverter::class),
        app(JpgToPngConverter::class),
        app(JpgToWebpConverter::class),
        app(JpgToPdfConverter::class),
    ]);
}

it('marks one png target as recommended', function () {
    $recommended = collect(mvpRegistry()->targetsFor('png'))
        ->filter(fn ($target) => $target->recommended === true);

    expect($recommended)->toHaveCount(1);
    expect($recommended->first()->format)->toBe('jpg');
});

it('marks one jpg target as recommended', function () {
    $recommended = collect(mvpRegistry()->targetsFor('jpg'))
        ->filter(fn ($target) => $target->recommended === true);

    expect($recommended)->toHaveCount(1);
    expect($recommended->first()->format)->toBe('webp');
});
