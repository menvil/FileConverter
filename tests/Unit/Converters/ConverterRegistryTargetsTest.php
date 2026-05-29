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
});

it('lists jpg target capabilities', function () {
    $registry = app(ConverterRegistry::class);

    $targets = collect($registry->targetsFor('jpg'))
        ->map(fn ($target) => $target->format)
        ->all();

    expect($targets)->toContain('png');
    expect($targets)->toContain('webp');
    expect($targets)->toContain('pdf');
    expect($targets)->not->toContain('mp3');
});

it('finds exact mvp converter pairs', function () {
    $registry = app(ConverterRegistry::class);

    expect($registry->find('png', 'jpg'))->not->toBeNull();
    expect($registry->find('png', 'webp'))->not->toBeNull();
    expect($registry->find('png', 'pdf'))->not->toBeNull();
    expect($registry->find('jpg', 'png'))->not->toBeNull();
    expect($registry->find('jpg', 'webp'))->not->toBeNull();
    expect($registry->find('jpg', 'pdf'))->not->toBeNull();
});

it('does not find unsupported converter pairs', function () {
    $registry = app(ConverterRegistry::class);

    expect($registry->find('png', 'mp3'))->toBeNull();
    expect($registry->find('pdf', 'docx'))->toBeNull();
});

it('returns empty target list for unsupported source format', function () {
    $registry = app(ConverterRegistry::class);

    expect($registry->targetsFor('mp4'))->toBe([]);
});
