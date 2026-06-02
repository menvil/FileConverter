<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Models\User;

it('shows user credit balance in dropdown', function () {
    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 25, 'test_extra_grant');

    $balance = app(CreditLedger::class)->balance($user);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Credits')
        ->assertSee((string) $balance);
});
