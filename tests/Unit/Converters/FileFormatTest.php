<?php

use App\Enums\FileFormat;

it('has file format enum with MVP cases', function () {
    expect(FileFormat::Png->value)->toBe('png');
    expect(FileFormat::Jpg->value)->toBe('jpg');
    expect(FileFormat::Webp->value)->toBe('webp');
    expect(FileFormat::Pdf->value)->toBe('pdf');
});
