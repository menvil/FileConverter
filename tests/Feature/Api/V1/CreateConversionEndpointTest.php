<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\Plan;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;
use Illuminate\Support\Facades\Queue;

it('creates conversion job through api', function () {
    Queue::fake();

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.source_format', 'png')
        ->assertJsonPath('data.target_format', 'jpg');

    $this->assertDatabaseHas('conversion_jobs', [
        'user_id' => $user->id,
        'source_file_id' => $file->id,
        'target_format' => 'jpg',
        'status' => 'queued',
    ]);
});

it('returns 402 when user has insufficient credits', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $balance = app(CreditLedger::class)->balance($user);
    if ($balance > 0) {
        app(CreditLedger::class)->spend($user, $balance, 'test_drain');
    }

    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertStatus(402)
        ->assertJsonPath('error.code', 'insufficient_credits');
});

it('rejects conversion for another users file', function () {
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
        ->assertForbidden();
});

it('requires api key to create conversion', function () {
    $this->postJson('/api/v1/conversions', [])
        ->assertUnauthorized();
});
