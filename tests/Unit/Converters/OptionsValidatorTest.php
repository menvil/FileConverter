<?php

use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use App\Support\Converters\OptionsValidator;

it('can instantiate options validator', function () {
    $validator = app(OptionsValidator::class);

    expect($validator)->toBeInstanceOf(OptionsValidator::class);
    expect(method_exists($validator, 'validate'))->toBeTrue();
});

it('applies default values from schema', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'segmented',
            'label' => 'Quality',
            'default' => 'high',
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    $normalized = app(OptionsValidator::class)->validate($schema, []);

    expect($normalized)->toBe(['quality' => 'high']);
});

it('allows valid user value to override default', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'segmented',
            'label' => 'Quality',
            'default' => 'high',
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    $normalized = app(OptionsValidator::class)->validate($schema, [
        'quality' => 'medium',
    ]);

    expect($normalized)->toBe(['quality' => 'medium']);
});

it('rejects invalid option value', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'select',
            'label' => 'Quality',
            'default' => 'high',
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    app(OptionsValidator::class)->validate($schema, [
        'quality' => 'ultra',
    ]);
})->throws(InvalidConverterOptionsException::class);

it('rejects unknown option key', function () {
    app(OptionsValidator::class)->validate([], [
        'unknown' => true,
    ]);
})->throws(InvalidConverterOptionsException::class);

it('normalizes toggle value to boolean', function () {
    $schema = [
        [
            'key' => 'remove_metadata',
            'type' => 'toggle',
            'label' => 'Remove metadata',
            'default' => false,
        ],
    ];

    $normalized = app(OptionsValidator::class)->validate($schema, [
        'remove_metadata' => 1,
    ]);

    expect($normalized)->toBe(['remove_metadata' => true]);
});

it('rejects required field without default or user value', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'segmented',
            'label' => 'Quality',
            'required' => true,
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    app(OptionsValidator::class)->validate($schema, []);
})->throws(InvalidConverterOptionsException::class);
