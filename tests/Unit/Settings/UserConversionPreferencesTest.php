<?php

declare(strict_types=1);

it('has default conversion preferences schema', function () {
    $defaults = config('converter.user_defaults');

    expect($defaults)->toHaveKey('image_quality');
    expect($defaults)->toHaveKey('remove_metadata');
});

it('has allowed image quality values', function () {
    $allowed = config('converter.allowed_image_quality_values');

    expect($allowed)->toContain('medium');
    expect($allowed)->toContain('high');
    expect($allowed)->toContain('best');
});
