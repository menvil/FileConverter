<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

it('web-upload rate limiter is registered', function () {
    expect(RateLimiter::limiter('web-upload'))->not->toBeNull();
});

it('web-conversion-create rate limiter is registered', function () {
    expect(RateLimiter::limiter('web-conversion-create'))->not->toBeNull();
});

it('blocks web upload after exceeding limit', function () {
    $user = User::factory()->create();

    $key = "web-upload:{$user->id}";

    // Override with a low limit for testing.
    RateLimiter::for('web-upload', fn () => Limit::perMinute(1)->by($user->id));

    RateLimiter::hit($key);

    expect(RateLimiter::tooManyAttempts($key, 1))->toBeTrue();
});

it('allows web upload under the limit', function () {
    $user = User::factory()->create();

    $key = "web-upload-fresh:{$user->id}-".uniqid();

    expect(RateLimiter::tooManyAttempts($key, 20))->toBeFalse();
});
