<?php

declare(strict_types=1);

use App\Support\Conversions\ConverterDriverRegistry;
use App\Support\Conversions\Exceptions\MissingConverterDriverException;
use Tests\Fakes\Conversions\FakeConverterDriver;

it('finds converter driver by key', function () {
    $driver = new FakeConverterDriver('png_to_jpg');
    $registry = new ConverterDriverRegistry([$driver]);

    expect($registry->find('png_to_jpg'))->toBe($driver);
});

it('returns null when driver is not found', function () {
    $registry = new ConverterDriverRegistry([]);

    expect($registry->find('missing_driver'))->toBeNull();
});

it('throws when driver is missing', function () {
    $registry = new ConverterDriverRegistry([]);

    $registry->findOrFail('missing_driver');
})->throws(MissingConverterDriverException::class);

it('rejects duplicate driver keys', function () {
    new ConverterDriverRegistry([
        new FakeConverterDriver('png_to_jpg'),
        new FakeConverterDriver('png_to_jpg'),
    ]);
})->throws(InvalidArgumentException::class);
