<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

final class ImageFixture
{
    public static function png(string $filename, int $width = 100, int $height = 100): string
    {
        $manager = new ImageManager(new Driver);
        $encoded = $manager->createImage($width, $height)->encodeUsingFileExtension('png');

        $path = 'fixtures/'.Str::random(8).'/'.$filename;
        Storage::disk('local')->put($path, (string) $encoded);

        return $path;
    }

    public static function jpg(string $filename, int $width = 100, int $height = 100): string
    {
        $manager = new ImageManager(new Driver);
        $encoded = $manager->createImage($width, $height)->encodeUsingFileExtension('jpg');

        $path = 'fixtures/'.Str::random(8).'/'.$filename;
        Storage::disk('local')->put($path, (string) $encoded);

        return $path;
    }
}
