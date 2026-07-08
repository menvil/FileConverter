<?php

use App\Support\Converters\Image\JpgToPdfConverter;
use App\Support\Converters\OptionsValidator;

it('defines jpg to pdf converter capability', function () {
    $converter = app(JpgToPdfConverter::class);

    expect($converter->key())->toBe('jpg:pdf');
    expect($converter->sourceFormat())->toBe('jpg');
    expect($converter->targetFormat())->toBe('pdf');
    expect($converter->label())->toBe('PDF');
});

it('provides valid default options for jpg to pdf', function () {
    $converter = app(JpgToPdfConverter::class);

    $options = app(OptionsValidator::class)->validate(
        $converter->optionsSchema(),
        []
    );

    expect($options)->toHaveKey('page_size');
    expect($options)->toHaveKey('orientation');
    expect($options)->toHaveKey('margin');
    expect($options)->toHaveKey('fit_mode');
    expect($options)->not->toHaveKey('compression');
});
