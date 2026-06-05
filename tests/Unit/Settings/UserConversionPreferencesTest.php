<?php

declare(strict_types=1);

it('has default conversion preferences schema', function () {
    $defaults = config('converter.user_defaults');

    expect($defaults)->toHaveKey('image_quality');
    expect($defaults['image_quality'])->toBe('high');
    expect($defaults)->toHaveKey('remove_metadata');
    expect($defaults['remove_metadata'])->toBeTrue();
});

it('has allowed image quality values', function () {
    $allowed = config('converter.allowed_image_quality_values');
    $defaultQuality = config('converter.user_defaults.image_quality');

    expect($allowed)->toContain('medium');
    expect($allowed)->toContain('high');
    expect($allowed)->toContain('best');
    expect($allowed)->toContain($defaultQuality);
});
