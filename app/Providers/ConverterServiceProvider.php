<?php

namespace App\Providers;

use App\Support\Conversions\ConverterDriverRegistry;
use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\Image\JpgToPdfConverter;
use App\Support\Converters\Image\JpgToPngConverter;
use App\Support\Converters\Image\JpgToWebpConverter;
use App\Support\Converters\Image\PngToJpgConverter;
use App\Support\Converters\Image\PngToPdfConverter;
use App\Support\Converters\Image\PngToWebpConverter;
use Illuminate\Support\ServiceProvider;

class ConverterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConverterRegistry::class, function ($app) {
            return new ConverterRegistry([
                $app->make(PngToJpgConverter::class),
                $app->make(PngToWebpConverter::class),
                $app->make(PngToPdfConverter::class),
                $app->make(JpgToPngConverter::class),
                $app->make(JpgToWebpConverter::class),
                $app->make(JpgToPdfConverter::class),
            ]);
        });

        // Runtime conversion drivers are registered here. Real image/PDF
        // drivers are introduced in Phase 10; for now the registry is empty.
        $this->app->singleton(ConverterDriverRegistry::class, function () {
            return new ConverterDriverRegistry([]);
        });
    }
}
