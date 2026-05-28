<?php

use App\Support\Converters\Contracts\Converter;

it('has converter contract with required methods', function () {
    $reflection = new ReflectionClass(Converter::class);

    expect($reflection->isInterface())->toBeTrue();

    foreach ([
        'key',
        'sourceFormat',
        'targetFormat',
        'label',
        'description',
        'optionsSchema',
        'validateOptions',
    ] as $method) {
        expect($reflection->hasMethod($method))->toBeTrue();
    }
});
