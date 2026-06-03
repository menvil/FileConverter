<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\Plan;
use App\Models\User;
use App\Services\Api\ApiKeyGenerator;

it('returns current credits balance through api', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    $balance = app(CreditLedger::class)->balance($user);

    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/credits/balance')
        ->assertOk()
        ->assertJsonPath('data.balance', $balance)
        ->assertJsonPath('data.unit', 'credits');
});

it('requires api key to get credits balance', function () {
    $this->getJson('/api/v1/credits/balance')
        ->assertUnauthorized();
});

it('rejects free user from credits balance endpoint', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);
    $token = app(ApiKeyGenerator::class)->create($user, 'Free')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/credits/balance')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'api_not_available');
});
