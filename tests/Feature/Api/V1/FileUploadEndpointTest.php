<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uploads file through api', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = UploadedFile::fake()->image('image.png', 100, 100);

    $this->withToken($token)
        ->postJson('/api/v1/files', ['file' => $file])
        ->assertCreated()
        ->assertJsonPath('data.original_name', 'image.png')
        ->assertJsonPath('data.extension', 'png')
        ->assertJsonStructure(['data' => ['id', 'original_name', 'extension', 'size_bytes', 'created_at']]);

    $this->assertDatabaseHas('files', [
        'user_id' => $user->id,
        'original_name' => 'image.png',
        'extension' => 'png',
    ]);
});

it('requires file field to upload', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->postJson('/api/v1/files', [])
        ->assertUnprocessable();
});

it('requires api key authentication to upload file', function () {
    $this->postJson('/api/v1/files', [])
        ->assertUnauthorized();
});

it('rejects free user from file upload endpoint', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);
    $token = app(ApiKeyGenerator::class)->create($user, 'Free')->plainToken;

    $this->withToken($token)
        ->postJson('/api/v1/files', [])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'api_not_available');
});
