<?php

declare(strict_types=1);

use App\Enums\FileStatus;
use App\Enums\Plan;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;
use Illuminate\Support\Facades\Storage;

it('downloads completed conversion result through api', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    Storage::disk('local')->put('results/result.jpg', 'fake-image-data');

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'results/result.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'original_name' => 'result.jpg',
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $resultFile->id,
    ]);

    $this->withToken($token)
        ->get("/api/v1/conversions/{$job->id}/download")
        ->assertOk();
});

it('returns 422 when conversion is not complete', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $job = ConversionJob::factory()->for($user)->queued()->create();

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}/download")
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'result_not_ready');
});

// CONV-344: Block expired API downloads

it('returns result_expired error for expired api download', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'results/expired.jpg',
        'expires_at' => now()->subDay(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $resultFile->id,
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}/download")
        ->assertStatus(410)
        ->assertJsonPath('error.code', 'result_expired')
        ->assertJsonPath('error.details.expired_at', $resultFile->expires_at->toIso8601String());
});

it('returns result_expired error for file with expired status', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'results/expired.jpg',
        'status' => FileStatus::Expired,
        'expires_at' => now()->addDay(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $resultFile->id,
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}/download")
        ->assertStatus(410)
        ->assertJsonPath('error.code', 'result_expired')
        ->assertJsonStructure(['error' => ['details' => ['expired_at']]]);
});

it('does not allow another user to download conversion result', function () {
    $owner = User::factory()->create(['plan' => Plan::Pro]);
    $other = User::factory()->create(['plan' => Plan::Pro]);

    $job = ConversionJob::factory()->for($owner)->completed()->create();
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}/download")
        ->assertForbidden();
});
