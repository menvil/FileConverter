<?php

declare(strict_types=1);

it('has conversion costs config for mvp converters', function () {
    expect(config('conversion_costs.rules.image_to_image.base'))->toBe(1);
    expect(config('conversion_costs.rules.image_to_pdf.base'))->toBe(2);
});

it('has image format group', function () {
    $imageFormats = config('conversion_costs.groups.image');

    expect($imageFormats)->toContain('png');
    expect($imageFormats)->toContain('jpg');
    expect($imageFormats)->toContain('jpeg');
    expect($imageFormats)->toContain('webp');
});

it('has pdf format group', function () {
    $pdfFormats = config('conversion_costs.groups.pdf');

    expect($pdfFormats)->toContain('pdf');
});
