<?php

declare(strict_types=1);

use App\Conversion\Drivers\Image\JpgToPngDriver;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ConversionContextFactory;
use Tests\Support\ImageFixture;

it('converts jpg to png', function () {
    Storage::fake('local');

    $sourcePath = ImageFixture::jpg('source.jpg', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'png',
        options: [
            'resize' => 'original',
            'remove_metadata' => true,
        ],
    );

    $result = app(JpgToPngDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('png');
    expect($result->mimeType)->toBe('image/png');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
