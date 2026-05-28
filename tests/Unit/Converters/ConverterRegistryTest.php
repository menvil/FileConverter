<?php

use App\Support\Converters\ConverterRegistry;
use Tests\Fakes\Converters\FakePngToJpgConverter;

it('can instantiate converter registry with converters', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter,
    ]);

    expect($registry)->toBeInstanceOf(ConverterRegistry::class);
});
