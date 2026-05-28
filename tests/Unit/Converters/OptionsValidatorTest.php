<?php

use App\Support\Converters\OptionsValidator;

it('can instantiate options validator', function () {
    $validator = app(OptionsValidator::class);

    expect($validator)->toBeInstanceOf(OptionsValidator::class);
    expect(method_exists($validator, 'validate'))->toBeTrue();
});
