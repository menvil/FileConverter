<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('protects all non-model-bound api v1 endpoints with api key authentication', function () {
    // Non-model-bound routes: auth middleware fires before anything else
    $this->getJson('/api/v1/converters')->assertUnauthorized();
    $this->getJson('/api/v1/converters/png/jpg/schema')->assertUnauthorized();
    $this->postJson('/api/v1/files')->assertUnauthorized();
    $this->postJson('/api/v1/conversions/estimate')->assertUnauthorized();
    $this->postJson('/api/v1/conversions')->assertUnauthorized();
    $this->getJson('/api/v1/credits/balance')->assertUnauthorized();
});

it('model-bound endpoints deny cross-user access with valid auth', function () {
    // SubstituteBindings runs before custom auth middleware, so testing with no
    // auth on model-bound routes produces 404 (not 401). Ownership enforcement
    // is covered comprehensively in ApiOwnershipGuardTest (CONV-310).
    // Here we verify a valid-auth user cannot access another user's resources.
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $file = FileRecord::factory()->for($owner)->create(['extension' => 'png']);
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertForbidden();
});

it('api health endpoint remains public', function () {
    $this->getJson('/api/v1/health')->assertOk();
});

it('free user is blocked from all protected api endpoints', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);
    $token = app(ApiKeyGenerator::class)->create($user, 'Free')->plainToken;

    $this->withToken($token)->getJson('/api/v1/converters')
        ->assertForbidden()->assertJsonPath('error.code', 'api_not_available');

    $this->withToken($token)->postJson('/api/v1/files')
        ->assertForbidden()->assertJsonPath('error.code', 'api_not_available');

    $this->withToken($token)->getJson('/api/v1/credits/balance')
        ->assertForbidden()->assertJsonPath('error.code', 'api_not_available');
});

it('pro user is allowed through api access gate', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'Pro')->plainToken;

    $this->withToken($token)->getJson('/api/v1/converters')->assertOk();
    $this->withToken($token)->getJson('/api/v1/credits/balance')->assertOk();
});
