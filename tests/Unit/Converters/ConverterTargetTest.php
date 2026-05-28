<?php

use App\Support\Converters\DTO\ConverterTarget;

it('creates converter target dto', function () {
    $target = new ConverterTarget(
        format: 'jpg',
        label: 'JPG',
        description: 'Best for photos and sharing',
        converterKey: 'png_to_jpg',
        recommended: true,
    );

    expect($target->format)->toBe('jpg');
    expect($target->label)->toBe('JPG');
    expect($target->description)->toBe('Best for photos and sharing');
    expect($target->converterKey)->toBe('png_to_jpg');
    expect($target->recommended)->toBeTrue();
});

it('exposes toArray representation', function () {
    $target = new ConverterTarget(
        format: 'pdf',
        label: 'PDF',
        description: 'Portable document',
        converterKey: 'png_to_pdf',
    );

    expect($target->toArray())->toBe([
        'format' => 'pdf',
        'label' => 'PDF',
        'description' => 'Portable document',
        'converter_key' => 'png_to_pdf',
        'recommended' => false,
    ]);
});
