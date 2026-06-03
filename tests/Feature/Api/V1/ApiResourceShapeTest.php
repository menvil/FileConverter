<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('file resource does not expose stored_path', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = UploadedFile::fake()->image('test.png', 10, 10);

    $response = $this->withToken($token)
        ->postJson('/api/v1/files', ['file' => $file])
        ->assertCreated();

    $data = $response->json('data');
    expect($data)->not->toHaveKey('stored_path');
    expect($data)->not->toHaveKey('checksum');
    expect($data)->toHaveKey('id');
    expect($data)->toHaveKey('original_name');
    expect($data)->toHaveKey('extension');
});

it('converter resource does not expose php class names', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/converters')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->not->toBeEmpty();

    foreach ($data as $converter) {
        expect($converter)->toHaveKey('key');
        expect($converter)->toHaveKey('source_format');
        expect($converter)->toHaveKey('target_format');
        expect($converter['key'])->not->toContain('\\');
    }
});

it('conversion resource does not expose internal fields', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $job = ConversionJob::factory()->for($user)->queued()->create();

    $response = $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}")
        ->assertOk();

    $data = $response->json('data');
    expect($data)->not->toHaveKey('converter_key');
    expect($data)->not->toHaveKey('options_json');
    expect($data)->not->toHaveKey('user_id');
    expect($data)->toHaveKey('id');
    expect($data)->toHaveKey('status');
});

it('api key resource does not expose token hash', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    // The token_hash should never appear in any API response
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $response = $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertOk();

    $body = json_encode($response->json());
    expect($body)->not->toContain('token_hash');
});
