<?php

use App\Support\Converters\Image\JpgToWebpConverter;
use App\Support\Converters\OptionsValidator;

it('defines jpg to webp converter capability', function () {
    $converter = app(JpgToWebpConverter::class);

    expect($converter->key())->toBe('jpg:webp');
    expect($converter->sourceFormat())->toBe('jpg');
    expect($converter->targetFormat())->toBe('webp');
    expect($converter->label())->toBe('WEBP');
});

it('provides valid default options for jpg to webp', function () {
    $converter = app(JpgToWebpConverter::class);

    $options = app(OptionsValidator::class)->validate(
        $converter->optionsSchema(),
        []
    );

    expect($options)->toHaveKey('quality');
    expect($options)->not->toHaveKey('resize');
    expect($options)->not->toHaveKey('remove_metadata');
});
