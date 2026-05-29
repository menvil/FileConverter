<?php

use App\Support\Converters\ConverterRegistry;

it('lists png target capabilities', function () {
    $registry = app(ConverterRegistry::class);

    $targets = collect($registry->targetsFor('png'))
        ->map(fn ($target) => $target->format)
        ->all();

    expect($targets)->toContain('jpg');
    expect($targets)->toContain('webp');
    expect($targets)->toContain('pdf');
    expect($targets)->not->toContain('mp3');
})->skip('CONV-056 wires the registry binding; this test will become live then.');

it('lists jpg target capabilities', function () {
    $registry = app(ConverterRegistry::class);

    $targets = collect($registry->targetsFor('jpg'))
        ->map(fn ($target) => $target->format)
        ->all();

    expect($targets)->toContain('png');
    expect($targets)->toContain('webp');
    expect($targets)->toContain('pdf');
    expect($targets)->not->toContain('mp3');
})->skip('CONV-056 wires the registry binding; this test will become live then.');
