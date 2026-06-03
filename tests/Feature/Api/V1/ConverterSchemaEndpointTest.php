<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('returns converter options schema for valid pair', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/converters/png/jpg/schema')
        ->assertOk()
        ->assertJsonPath('data.source_format', 'png')
        ->assertJsonPath('data.target_format', 'jpg')
        ->assertJsonStructure([
            'data' => [
                'source_format',
                'target_format',
                'options',
            ],
        ]);
});

it('returns 422 for unsupported conversion pair', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/converters/png/mp3/schema')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'unsupported_conversion');
});

it('requires api key authentication to get converter schema', function () {
    $this->getJson('/api/v1/converters/png/jpg/schema')
        ->assertUnauthorized();
});
