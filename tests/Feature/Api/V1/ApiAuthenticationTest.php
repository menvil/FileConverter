<?php

declare(strict_types=1);

use App\Models\ApiKey;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('rejects api request without bearer token', function () {
    $this->getJson('/api/v1/auth-test')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthorized');
});

it('rejects api request with invalid bearer token', function () {
    $this->withToken('fc_invalid_token_that_does_not_exist')
        ->getJson('/api/v1/auth-test')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthorized');
});

it('rejects api request with revoked api key', function () {
    $user = User::factory()->create();
    $generated = app(ApiKeyGenerator::class)->create($user, 'Test');

    ApiKey::where('id', $generated->apiKey->id)->update(['revoked_at' => now()]);

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/auth-test')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthorized');
});

it('authenticates api request with valid api key', function () {
    $user = User::factory()->create();
    $generated = app(ApiKeyGenerator::class)->create($user, 'Test');

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/auth-test')
        ->assertOk()
        ->assertJsonPath('authenticated', true);
});

it('updates last used at on successful authentication', function () {
    $user = User::factory()->create();
    $generated = app(ApiKeyGenerator::class)->create($user, 'Test');

    expect($generated->apiKey->fresh()->last_used_at)->toBeNull();

    $this->withToken($generated->plainToken)->getJson('/api/v1/auth-test');

    expect($generated->apiKey->fresh()->last_used_at)->not->toBeNull();
});
