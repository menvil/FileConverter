<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guest from billing page to login', function () {
    $this->get('/billing')
        ->assertRedirect('/login');
});

it('allows authenticated user to access billing page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing')
        ->assertOk()
        ->assertSee('Billing');
});
