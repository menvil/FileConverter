<?php

use App\Support\Converters\Image\PngToWebpConverter;
use App\Support\Converters\OptionsValidator;

it('defines png to webp converter capability', function () {
    $converter = app(PngToWebpConverter::class);

    expect($converter->key())->toBe('png:webp');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('webp');
    expect($converter->label())->toBe('WEBP');
    expect($converter->description())->not->toBeEmpty();
});

it('provides valid default options for png to webp', function () {
    $converter = app(PngToWebpConverter::class);

    $options = app(OptionsValidator::class)->validate(
        $converter->optionsSchema(),
        []
    );

    expect($options)->toHaveKey('quality');
    expect($options)->toHaveKey('lossless');
    expect($options)->toHaveKey('resize');
    expect($options)->toHaveKey('remove_metadata');
    expect($options['lossless'])->toBeFalse();
});
