<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('returns target formats for uploaded file', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['target_format', 'label', 'description', 'recommended'],
            ],
        ]);

    $formats = collect($response->json('data'))->pluck('target_format')->toArray();
    expect($formats)->toContain('jpg');
});

it('does not return targets for another users file', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $file = FileRecord::factory()->for($owner)->create(['extension' => 'png']);
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertForbidden();
});

it('requires api key to get file targets', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->getJson("/api/v1/files/{$file->id}/targets")
        ->assertUnauthorized();
});

it('returns empty list for unsupported source format', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = FileRecord::factory()->for($user)->create(['extension' => 'xyz']);

    $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertOk()
        ->assertJsonPath('data', []);
});
