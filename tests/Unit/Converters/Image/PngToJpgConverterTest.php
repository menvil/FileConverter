<?php

use App\Support\Converters\Image\PngToJpgConverter;
use App\Support\Converters\OptionsValidator;

it('defines png to jpg converter capability', function () {
    $converter = app(PngToJpgConverter::class);

    expect($converter->key())->toBe('png:jpg');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('jpg');
    expect($converter->label())->toBe('JPG');
    expect($converter->description())->not->toBeEmpty();
});

it('provides valid default options for png to jpg', function () {
    $converter = app(PngToJpgConverter::class);

    $options = app(OptionsValidator::class)->validate(
        $converter->optionsSchema(),
        []
    );

    expect($options)->toHaveKey('quality');
    expect($options)->toHaveKey('resize');
    expect($options)->toHaveKey('background_color');
    expect($options)->toHaveKey('remove_metadata');
    expect($options['background_color'])->toBe('#ffffff');
    expect($options['remove_metadata'])->toBeTrue();
});
