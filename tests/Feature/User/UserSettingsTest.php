<?php

use App\Models\User;

it('casts user settings to array', function () {
    $user = User::factory()->create([
        'settings' => ['default_image_quality' => 'high'],
    ]);

    expect($user->fresh()->settings)
        ->toBeArray()
        ->and($user->fresh()->settings['default_image_quality'])
        ->toBe('high');
});

it('defaults user settings to an empty array', function () {
    $user = User::factory()->create();

    expect($user->fresh()->settings)->toBeArray();
});
