<?php

use App\Enums\FileFormat;
use App\Support\Converters\Exceptions\UnsupportedFormatException;

it('has file format enum with MVP cases', function () {
    expect(FileFormat::Png->value)->toBe('png');
    expect(FileFormat::Jpg->value)->toBe('jpg');
    expect(FileFormat::Webp->value)->toBe('webp');
    expect(FileFormat::Pdf->value)->toBe('pdf');
});

it('normalizes jpeg to jpg', function () {
    expect(FileFormat::normalize('jpeg'))->toBe('jpg');
});

it('normalizes uppercase input', function () {
    expect(FileFormat::normalize('PNG'))->toBe('png');
});

it('normalizes extension with leading dot', function () {
    expect(FileFormat::normalize('.webp'))->toBe('webp');
});

it('rejects unsupported format', function () {
    FileFormat::normalize('mp3');
})->throws(UnsupportedFormatException::class);
