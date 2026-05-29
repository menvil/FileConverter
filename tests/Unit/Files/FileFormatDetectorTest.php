<?php

use App\Exceptions\Files\UnsupportedFileFormatException;
use App\Support\Files\FileFormatDetector;
use Illuminate\Http\UploadedFile;

it('detects png format', function () {
    $file = UploadedFile::fake()->image('test.png');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('png');
});

it('detects jpg format', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('jpg');
});

it('normalizes jpeg extension to jpg', function () {
    $file = UploadedFile::fake()->image('test.jpeg');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('jpg');
});

it('detects webp format by mime', function () {
    $file = UploadedFile::fake()->create('test.webp', 10, 'image/webp');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('webp');
});

it('detects pdf format by mime', function () {
    $file = UploadedFile::fake()->create('test.pdf', 10, 'application/pdf');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('pdf');
});

it('rejects unsupported file format', function () {
    $file = UploadedFile::fake()->create('test.txt', 10, 'text/plain');

    app(FileFormatDetector::class)->detect($file);
})->throws(UnsupportedFileFormatException::class);
