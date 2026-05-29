<?php

use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\OptionsSchemaValidator;
use App\Support\Converters\OptionsValidator;

it('contains only the expected mvp converter capabilities', function () {
    $registry = app(ConverterRegistry::class);

    $keys = collect($registry->all())
        ->map(fn ($converter) => $converter->key())
        ->sort()
        ->values()
        ->all();

    expect($keys)->toBe([
        'jpg:pdf',
        'jpg:png',
        'jpg:webp',
        'png:jpg',
        'png:pdf',
        'png:webp',
    ]);
});

it('has valid schemas and default options for every mvp converter', function () {
    $registry = app(ConverterRegistry::class);
    $schemaValidator = app(OptionsSchemaValidator::class);
    $optionsValidator = app(OptionsValidator::class);

    foreach ($registry->all() as $converter) {
        $schemaValidator->validate($converter->optionsSchema());

        $options = $optionsValidator->validate($converter->optionsSchema(), []);

        expect($options)->toBeArray();
    }
});

it('exposes a label and description for every mvp converter', function () {
    $registry = app(ConverterRegistry::class);

    foreach ($registry->all() as $converter) {
        expect($converter->label())->not->toBeEmpty();
        expect($converter->description())->not->toBeEmpty();
    }
});

it('mvp capability list matches registered converter keys', function () {
    $registry = app(ConverterRegistry::class);

    $registered = collect($registry->all())
        ->map(fn ($converter) => $converter->key())
        ->sort()
        ->values()
        ->all();

    $declared = collect(config('converters.mvp_capabilities'))
        ->map(fn ($key) => str_replace(':', ':', $key))
        ->sort()
        ->values()
        ->all();

    expect($registered)->toBe($declared);
});
