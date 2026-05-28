<?php

use App\Models\User;

it('returns user display name', function () {
    $user = new User(['name' => 'Alex Johnson', 'email' => 'alex@example.com']);

    expect($user->displayName())->toBe('Alex Johnson');
});

it('uses email as display fallback when name is missing', function () {
    $user = new User(['name' => '', 'email' => 'alex@example.com']);

    expect($user->displayName())->toBe('alex@example.com');
});

it('returns user initials from a multi-word name', function () {
    $user = new User(['name' => 'Alex Johnson']);

    expect($user->initials())->toBe('AJ');
});

it('returns initials from a single-word name', function () {
    $user = new User(['name' => 'Madonna']);

    expect($user->initials())->toBe('MA');
});

it('returns initials from email when name is missing', function () {
    $user = new User(['name' => '', 'email' => 'alex@example.com']);

    expect($user->initials())->toBe('AL');
});

it('returns a placeholder when name and email are both empty', function () {
    $user = new User(['name' => '', 'email' => '']);

    expect($user->initials())->toBe('U');
});
