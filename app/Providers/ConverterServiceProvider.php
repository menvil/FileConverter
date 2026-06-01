<?php

namespace App\Providers;

use App\Conversion\Drivers\Image\JpgToPdfDriver;
use App\Conversion\Drivers\Image\JpgToPngDriver;
use App\Conversion\Drivers\Image\JpgToWebpDriver;
use App\Conversion\Drivers\Image\PngToJpgDriver;
use App\Conversion\Drivers\Image\PngToPdfDriver;
use App\Conversion\Drivers\Image\PngToWebpDriver;
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

        $this->app->singleton(ConverterDriverRegistry::class, function ($app) {
            return new ConverterDriverRegistry([
                $app->make(PngToJpgDriver::class),
                $app->make(JpgToPngDriver::class),
                $app->make(PngToWebpDriver::class),
                $app->make(JpgToWebpDriver::class),
                $app->make(PngToPdfDriver::class),
                $app->make(JpgToPdfDriver::class),
            ]);
        });
    }
}
