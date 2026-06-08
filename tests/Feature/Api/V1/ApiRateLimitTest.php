<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

it('rate limiter api-v1 is registered', function () {
    expect(RateLimiter::limiter('api-v1'))->not->toBeNull();
});

it('returns 429 when api rate limit is exceeded on a throttled endpoint', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'Pro key')->plainToken;

    // Override the limiter with a limit of 1 to make testing practical.
    RateLimiter::for('api-v1', fn () => Limit::perMinute(1)->by($user->id));

    // First request should succeed.
    $this->withToken($token)->getJson('/api/v1/converters')->assertOk();

    // Second request exceeds the limit.
    $this->withToken($token)->getJson('/api/v1/converters')->assertStatus(429);
});

it('returns stable json rate_limited error when api limit is exceeded', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'Pro key')->plainToken;

    RateLimiter::for('api-v1', fn () => Limit::perMinute(1)->by($user->id));

    $this->withToken($token)->getJson('/api/v1/converters')->assertOk();

    $this->withToken($token)->getJson('/api/v1/converters')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'rate_limited')
        ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
});
