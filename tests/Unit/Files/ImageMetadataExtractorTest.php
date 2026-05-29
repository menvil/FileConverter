<?php

use App\Support\Files\ImageMetadataExtractor;
use Illuminate\Http\UploadedFile;

it('extracts png dimensions', function () {
    $file = UploadedFile::fake()->image('image.png', 1200, 800);

    $metadata = app(ImageMetadataExtractor::class)->extract($file, 'png');

    expect($metadata['width'])->toBe(1200);
    expect($metadata['height'])->toBe(800);
    expect($metadata)->toHaveKey('supports_transparency');
    expect($metadata['supports_transparency'])->toBeTrue();
});

it('extracts jpg dimensions and reports no transparency', function () {
    $file = UploadedFile::fake()->image('image.jpg', 640, 480);

    $metadata = app(ImageMetadataExtractor::class)->extract($file, 'jpg');

    expect($metadata['width'])->toBe(640);
    expect($metadata['height'])->toBe(480);
    expect($metadata['supports_transparency'])->toBeFalse();
});

it('returns empty metadata for pdf', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');

    $metadata = app(ImageMetadataExtractor::class)->extract($file, 'pdf');

    expect($metadata)->toBeArray();
    expect($metadata)->toBeEmpty();
});
