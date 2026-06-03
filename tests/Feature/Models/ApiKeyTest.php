<?php

declare(strict_types=1);

use App\Models\ApiKey;
use App\Models\User;

it('belongs api key to user', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->for($user)->create();

    expect($apiKey->user->is($user))->toBeTrue();
    expect($user->apiKeys()->first()->is($apiKey))->toBeTrue();
});

it('checks if api key is revoked', function () {
    $active = ApiKey::factory()->create(['revoked_at' => null]);
    $revoked = ApiKey::factory()->create(['revoked_at' => now()]);

    expect($active->isRevoked())->toBeFalse();
    expect($revoked->isRevoked())->toBeTrue();
});
