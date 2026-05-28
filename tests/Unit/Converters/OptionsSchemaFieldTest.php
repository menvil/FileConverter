<?php

use App\Support\Converters\DTO\OptionsSchemaField;

it('creates options schema field dto', function () {
    $field = new OptionsSchemaField(
        key: 'quality',
        type: 'segmented',
        label: 'Quality',
        default: 'high',
        options: [
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'high', 'label' => 'High'],
        ],
        required: false,
    );

    expect($field->key)->toBe('quality');
    expect($field->type)->toBe('segmented');
    expect($field->label)->toBe('Quality');
    expect($field->default)->toBe('high');
    expect($field->options)->toHaveCount(2);
    expect($field->required)->toBeFalse();
});

it('exposes toArray representation', function () {
    $field = new OptionsSchemaField(
        key: 'remove_metadata',
        type: 'toggle',
        label: 'Remove metadata',
        default: false,
        help: 'Strips EXIF data',
    );

    expect($field->toArray())->toBe([
        'key' => 'remove_metadata',
        'type' => 'toggle',
        'label' => 'Remove metadata',
        'default' => false,
        'options' => [],
        'required' => false,
        'help' => 'Strips EXIF data',
    ]);
});
