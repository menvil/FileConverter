<?php

declare(strict_types=1);

use App\Conversion\Drivers\Image\JpgToWebpDriver;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ConversionContextFactory;
use Tests\Support\ImageFixture;

it('converts jpg to webp', function () {
    Storage::fake('local');

    $sourcePath = ImageFixture::jpg('source.jpg', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'webp',
        options: [
            'quality' => 'high',
            'resize' => 'original',
            'remove_metadata' => true,
        ],
    );

    $result = app(JpgToWebpDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('webp');
    expect($result->mimeType)->toBe('image/webp');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
