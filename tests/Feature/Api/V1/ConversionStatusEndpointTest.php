<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\ConversionJob;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('returns conversion status through api', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $job = ConversionJob::factory()->for($user)->queued()->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $job->id)
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.source_format', 'png')
        ->assertJsonPath('data.target_format', 'jpg')
        ->assertJsonStructure([
            'data' => [
                'id', 'status', 'progress', 'source_format', 'target_format',
                'created_at', 'started_at', 'completed_at',
            ],
        ]);
});

it('does not expose another users conversion status', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $job = ConversionJob::factory()->for($owner)->queued()->create();
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}")
        ->assertForbidden();
});

it('requires api key to get conversion status', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $job = ConversionJob::factory()->for($user)->queued()->create();

    $this->getJson("/api/v1/conversions/{$job->id}")
        ->assertUnauthorized();
});
