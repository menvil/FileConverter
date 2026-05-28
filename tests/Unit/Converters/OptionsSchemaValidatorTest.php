<?php

use App\Support\Converters\Exceptions\InvalidOptionsSchemaException;
use App\Support\Converters\OptionsSchemaValidator;

it('accepts valid options schema', function () {
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

    app(OptionsSchemaValidator::class)->validate($schema);

    expect(true)->toBeTrue();
});

it('rejects field without key', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['type' => 'select', 'label' => 'Quality', 'options' => [['value' => 'a', 'label' => 'A']]],
    ]);
})->throws(InvalidOptionsSchemaException::class);

it('rejects field without type', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['key' => 'quality', 'label' => 'Quality'],
    ]);
})->throws(InvalidOptionsSchemaException::class);

it('rejects unsupported field type', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['key' => 'quality', 'type' => 'magic', 'label' => 'Quality'],
    ]);
})->throws(InvalidOptionsSchemaException::class);

it('rejects duplicate field keys', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['key' => 'quality', 'type' => 'toggle', 'label' => 'Quality'],
        ['key' => 'quality', 'type' => 'toggle', 'label' => 'Quality Again'],
    ]);
})->throws(InvalidOptionsSchemaException::class);

it('requires options for select and segmented fields', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['key' => 'quality', 'type' => 'select', 'label' => 'Quality'],
    ]);
})->throws(InvalidOptionsSchemaException::class);
