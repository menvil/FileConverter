<?php

declare(strict_types=1);

use App\Models\User;

it('shows buy credits cta in user dropdown', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Buy credits');
});
