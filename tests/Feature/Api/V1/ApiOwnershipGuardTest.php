<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('does not allow api user to access another users file targets', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $file = FileRecord::factory()->for($owner)->create(['extension' => 'png']);
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

it('does not allow api user to estimate cost on another users file', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $file = FileRecord::factory()->for($owner)->create(['extension' => 'png']);
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

it('does not allow api user to create conversion on another users file', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $file = FileRecord::factory()->for($owner)->create(['extension' => 'png']);
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->postJson('/api/v1/conversions', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

it('does not allow api user to get another users conversion status', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $job = ConversionJob::factory()->for($owner)->queued()->create();
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}")
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});

it('does not allow api user to download another users conversion', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $job = ConversionJob::factory()->for($owner)->queued()->create();
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}/download")
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});
