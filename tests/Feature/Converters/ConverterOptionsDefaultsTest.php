<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Converters\UserConverterDefaultsResolver;

it('applies user image quality preference to converter options defaults', function () {
    $user = User::factory()->create([
        'settings' => [
            'conversion' => [
                'image_quality' => 'best',
                'remove_metadata' => true,
            ],
        ],
    ]);

    $schema = [
        ['key' => 'quality', 'type' => 'segmented', 'label' => 'Quality', 'default' => 'high', 'options' => []],
        ['key' => 'remove_metadata', 'type' => 'toggle', 'label' => 'Remove metadata', 'default' => true],
    ];

    $resolver = app(UserConverterDefaultsResolver::class);
    $defaults = $resolver->apply($user, $schema);

    expect($defaults['quality'])->toBe('best');
    expect($defaults['remove_metadata'])->toBeTrue();
});

it('applies user remove metadata false preference to converter options defaults', function () {
    $user = User::factory()->create([
        'settings' => [
            'conversion' => [
                'image_quality' => 'medium',
                'remove_metadata' => false,
            ],
        ],
    ]);

    $schema = [
        ['key' => 'quality', 'type' => 'segmented', 'label' => 'Quality', 'default' => 'high', 'options' => []],
        ['key' => 'remove_metadata', 'type' => 'toggle', 'label' => 'Remove metadata', 'default' => true],
    ];

    $resolver = app(UserConverterDefaultsResolver::class);
    $defaults = $resolver->apply($user, $schema);

    expect($defaults['quality'])->toBe('medium');
    expect($defaults['remove_metadata'])->toBeFalse();
});

it('uses system defaults when user has no conversion preferences', function () {
    $user = User::factory()->create([
        'settings' => [],
    ]);

    $schema = [
        ['key' => 'quality', 'type' => 'segmented', 'label' => 'Quality', 'default' => 'high', 'options' => []],
        ['key' => 'remove_metadata', 'type' => 'toggle', 'label' => 'Remove metadata', 'default' => true],
    ];

    $resolver = app(UserConverterDefaultsResolver::class);
    $defaults = $resolver->apply($user, $schema);

    expect($defaults['quality'])->toBe(config('converter.user_defaults.image_quality'));
    expect($defaults['remove_metadata'])->toBe(config('converter.user_defaults.remove_metadata'));
});

it('falls back to system default when persisted image_quality is invalid', function () {
    $user = User::factory()->create([
        'settings' => [
            'conversion' => [
                'image_quality' => 'invalid_value',
            ],
        ],
    ]);

    $schema = [
        ['key' => 'quality', 'type' => 'segmented', 'label' => 'Quality', 'default' => 'high', 'options' => []],
        ['key' => 'remove_metadata', 'type' => 'toggle', 'label' => 'Remove metadata', 'default' => true],
    ];

    $resolver = app(UserConverterDefaultsResolver::class);
    $defaults = $resolver->apply($user, $schema);

    expect($defaults['quality'])->toBe(config('converter.user_defaults.image_quality'));
});

it('does not inject quality default into converters that lack a quality field', function () {
    $user = User::factory()->create([
        'settings' => [
            'conversion' => [
                'image_quality' => 'best',
            ],
        ],
    ]);

    $schema = [
        ['key' => 'compression', 'type' => 'select', 'label' => 'Compression', 'default' => 'none', 'options' => []],
    ];

    $resolver = app(UserConverterDefaultsResolver::class);
    $defaults = $resolver->apply($user, $schema);

    expect($defaults)->not->toHaveKey('quality');
    expect($defaults['compression'])->toBe('none');
});
