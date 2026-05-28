<?php

use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\DTO\ConverterTarget;
use Tests\Fakes\Converters\FakeJpgToWebpConverter;
use Tests\Fakes\Converters\FakePngToJpgConverter;
use Tests\Fakes\Converters\FakePngToPdfConverter;

it('can instantiate converter registry with converters', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter,
    ]);

    expect($registry)->toBeInstanceOf(ConverterRegistry::class);
});

it('returns all converters', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter,
        new FakePngToPdfConverter,
    ]);

    expect($registry->all())->toHaveCount(2);
});

it('returns converters for source format', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter,
        new FakePngToPdfConverter,
        new FakeJpgToWebpConverter,
    ]);

    expect($registry->forSource('png'))->toHaveCount(2);
    expect($registry->forSource('jpg'))->toHaveCount(1);
});

it('finds exact source target converter', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter,
        new FakePngToPdfConverter,
    ]);

    expect($registry->find('png', 'jpg'))->toBeInstanceOf(FakePngToJpgConverter::class);
});

it('returns null for unsupported conversion pair', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter,
    ]);

    expect($registry->find('png', 'pdf'))->toBeNull();
});

it('returns target cards for source format', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter,
        new FakePngToPdfConverter,
    ]);

    $targets = $registry->targetsFor('png');

    expect($targets)->toHaveCount(2);
    expect($targets[0])->toBeInstanceOf(ConverterTarget::class);
});
