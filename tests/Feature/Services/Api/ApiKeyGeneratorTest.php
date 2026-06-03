<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('creates api key and returns plain token once', function () {
    $user = User::factory()->create();

    $result = app(ApiKeyGenerator::class)->create($user, 'Test key');

    expect($result->plainToken)->toStartWith('fc_');
    expect($result->apiKey->name)->toBe('Test key');
    expect($result->apiKey->token_hash)->not->toBe($result->plainToken);
    expect(hash('sha256', $result->plainToken))->toBe($result->apiKey->token_hash);
});

it('stores api key for the given user', function () {
    $user = User::factory()->create();

    $result = app(ApiKeyGenerator::class)->create($user, 'My key');

    expect($result->apiKey->user_id)->toBe($user->id);
    $this->assertDatabaseHas('api_keys', [
        'user_id' => $user->id,
        'name' => 'My key',
        'token_hash' => hash('sha256', $result->plainToken),
    ]);
});

it('does not store plain token in database', function () {
    $user = User::factory()->create();

    $result = app(ApiKeyGenerator::class)->create($user, 'Secure key');

    $this->assertDatabaseMissing('api_keys', [
        'token_hash' => $result->plainToken,
    ]);
});
