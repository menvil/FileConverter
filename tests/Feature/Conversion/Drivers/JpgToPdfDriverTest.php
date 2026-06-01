<?php

declare(strict_types=1);

use App\Conversion\Drivers\Image\JpgToPdfDriver;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ConversionContextFactory;
use Tests\Support\ImageFixture;

it('converts jpg to pdf', function () {
    Storage::fake('local');

    $sourcePath = ImageFixture::jpg('source.jpg', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'pdf',
        options: [
            'page_size' => 'a4',
            'orientation' => 'auto',
            'margin' => 'small',
            'fit_mode' => 'contain',
            'compression' => 'balanced',
        ],
    );

    $result = app(JpgToPdfDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('pdf');
    expect($result->mimeType)->toBe('application/pdf');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
