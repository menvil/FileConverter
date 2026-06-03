<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('rejects free user from protected api endpoints even with valid api key', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);
    $generated = app(ApiKeyGenerator::class)->create($user, 'Free key');

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/access-test')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'api_not_available');
});

it('allows pro user to access protected api endpoints', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $generated = app(ApiKeyGenerator::class)->create($user, 'Pro key');

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/access-test')
        ->assertOk();
});

it('allows max user to access protected api endpoints', function () {
    $user = User::factory()->create(['plan' => Plan::Max]);
    $generated = app(ApiKeyGenerator::class)->create($user, 'Max key');

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/access-test')
        ->assertOk();
});
