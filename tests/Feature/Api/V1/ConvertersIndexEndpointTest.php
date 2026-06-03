<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('returns available converters for api user', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/converters')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'key',
                    'source_format',
                    'target_format',
                    'label',
                    'description',
                ],
            ],
        ]);
});

it('returns non-empty converters list', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/converters')
        ->assertOk();

    expect($response->json('data'))->not->toBeEmpty();
});

it('requires api key authentication to list converters', function () {
    $this->getJson('/api/v1/converters')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthorized');
});

it('rejects free user from converters endpoint', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);
    $token = app(ApiKeyGenerator::class)->create($user, 'Free')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/converters')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'api_not_available');
});
