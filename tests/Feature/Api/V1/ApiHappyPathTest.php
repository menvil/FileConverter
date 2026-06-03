<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\Plan;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('runs full api conversion happy path', function () {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    // 1. Upload file
    $uploadResponse = $this->withToken($token)
        ->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->image('image.png', 100, 100),
        ])
        ->assertCreated();

    $fileId = $uploadResponse->json('data.id');
    expect($fileId)->toBeInt();

    // 2. Get target formats
    $this->withToken($token)
        ->getJson("/api/v1/files/{$fileId}/targets")
        ->assertOk()
        ->assertJsonFragment(['target_format' => 'jpg']);

    // 3. Estimate conversion cost
    $estimateResponse = $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $fileId,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertOk();

    expect($estimateResponse->json('data.amount'))->toBeGreaterThan(0);
    expect($estimateResponse->json('data.has_enough_credits'))->toBeTrue();
    $this->assertDatabaseCount('conversion_jobs', 0);

    // 4. Create conversion job
    $conversionResponse = $this->withToken($token)
        ->postJson('/api/v1/conversions', [
            'file_id' => $fileId,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertCreated();

    $conversionId = $conversionResponse->json('data.id');
    expect($conversionId)->toBeInt();
    expect($conversionResponse->json('data.status'))->toBe('queued');

    // 5. Check conversion status
    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$conversionId}")
        ->assertOk()
        ->assertJsonPath('data.id', $conversionId)
        ->assertJsonPath('data.status', 'queued');

    $this->assertDatabaseHas('conversion_jobs', [
        'id' => $conversionId,
        'status' => 'queued',
    ]);
});

it('api key authentication is required for all business endpoints', function () {
    $this->getJson('/api/v1/converters')->assertUnauthorized();
    $this->postJson('/api/v1/files')->assertUnauthorized();
    $this->postJson('/api/v1/conversions/estimate')->assertUnauthorized();
    $this->postJson('/api/v1/conversions')->assertUnauthorized();
    $this->getJson('/api/v1/credits/balance')->assertUnauthorized();
});

it('returns credits balance during happy path', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $balance = app(CreditLedger::class)->balance($user);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/credits/balance')
        ->assertOk()
        ->assertJsonPath('data.balance', $balance)
        ->assertJsonPath('data.unit', 'credits');
});
