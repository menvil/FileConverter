<?php

declare(strict_types=1);

use App\Support\Conversions\DTO\ConversionResult;

it('creates conversion result dto', function () {
    $result = new ConversionResult(
        path: 'conversions/results/output.jpg',
        originalName: 'image.jpg',
        mimeType: 'image/jpeg',
        extension: 'jpg',
        sizeBytes: 12345,
        metadata: ['width' => 100, 'height' => 100],
    );

    expect($result->extension)->toBe('jpg');
    expect($result->sizeBytes)->toBe(12345);
    expect($result->metadata['width'])->toBe(100);
});
