<?php

declare(strict_types=1);

use App\Conversion\Drivers\Image\PngToJpgDriver;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ConversionContextFactory;
use Tests\Support\ImageFixture;

it('converts png to jpg', function () {
    Storage::fake('local');

    $sourcePath = ImageFixture::png('source.png', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        options: [
            'quality' => 'high',
            'background' => '#ffffff',
            'resize' => 'original',
            'remove_metadata' => true,
        ],
    );

    $result = app(PngToJpgDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('jpg');
    expect($result->mimeType)->toBe('image/jpeg');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
