<?php

use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\DTO\ConverterTarget;
use App\ViewModels\TargetFormatCardViewModel;

it('creates target format card data from converter metadata', function () {
    $converter = app(ConverterRegistry::class)->find('png', 'jpg');

    $card = TargetFormatCardViewModel::fromConverter($converter);

    expect($card->targetFormat)->toBe('jpg');
    expect($card->label)->toBe('JPG');
    expect($card->description)->not->toBeEmpty();
    expect($card->recommended)->toBeTrue();
});

it('creates target format card data from a converter target dto', function () {
    $target = new ConverterTarget(
        format: 'webp',
        label: 'WEBP',
        description: 'Smaller modern web image',
        converterKey: 'png:webp',
        recommended: false,
    );

    $card = TargetFormatCardViewModel::fromTarget($target);

    expect($card->targetFormat)->toBe('webp');
    expect($card->label)->toBe('WEBP');
    expect($card->description)->toBe('Smaller modern web image');
    expect($card->recommended)->toBeFalse();
});
