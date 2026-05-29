<?php

use App\Support\Converters\Image\PngToPdfConverter;
use App\Support\Converters\OptionsValidator;

it('defines png to pdf converter capability', function () {
    $converter = app(PngToPdfConverter::class);

    expect($converter->key())->toBe('png:pdf');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('pdf');
    expect($converter->label())->toBe('PDF');
    expect($converter->description())->not->toBeEmpty();
});

it('provides valid default options for png to pdf', function () {
    $converter = app(PngToPdfConverter::class);

    $options = app(OptionsValidator::class)->validate(
        $converter->optionsSchema(),
        []
    );

    expect($options)->toHaveKey('page_size');
    expect($options)->toHaveKey('orientation');
    expect($options)->toHaveKey('margin');
    expect($options)->toHaveKey('fit_mode');
    expect($options)->toHaveKey('compression');
});
