<?php

use App\Support\Converters\Image\JpgToPngConverter;
use App\Support\Converters\OptionsValidator;

it('defines jpg to png converter capability', function () {
    $converter = app(JpgToPngConverter::class);

    expect($converter->key())->toBe('jpg:png');
    expect($converter->sourceFormat())->toBe('jpg');
    expect($converter->targetFormat())->toBe('png');
    expect($converter->label())->toBe('PNG');
    expect($converter->description())->not->toBeEmpty();
});

it('provides valid default options for jpg to png', function () {
    $converter = app(JpgToPngConverter::class);

    $options = app(OptionsValidator::class)->validate(
        $converter->optionsSchema(),
        []
    );

    expect($options)->not->toHaveKey('resize');
    expect($options)->not->toHaveKey('remove_metadata');
    expect($options)->not->toHaveKey('transparency');
});
