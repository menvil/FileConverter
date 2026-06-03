<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\Plan;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('estimates conversion cost through api', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    app(CreditLedger::class)->grant($user, 10, 'test_grant');
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['amount', 'breakdown', 'has_enough_credits'],
        ]);

    $this->assertDatabaseCount('conversion_jobs', 0);
});

it('does not create conversion job when estimating', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    app(CreditLedger::class)->grant($user, 10, 'test_grant');
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertOk();

    $this->assertDatabaseCount('conversion_jobs', 0);
});

it('returns has_enough_credits false when user has no credits', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $balance = app(CreditLedger::class)->balance($user);
    if ($balance > 0) {
        app(CreditLedger::class)->spend($user, $balance, 'test_drain');
    }

    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [],
        ])
        ->assertOk();

    expect($response->json('data.has_enough_credits'))->toBeFalse();
});

it('rejects estimate for another users file', function () {
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
        ->assertForbidden();
});

it('returns 422 for unsupported conversion pair in estimate', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $file->id,
            'target_format' => 'mp3',
            'options' => [],
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'unsupported_conversion');
});
