<?php

declare(strict_types=1);

use App\Models\User;

it('allows authenticated user to access settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk()
        ->assertSee('Settings');
});

it('redirects guest from settings page to login', function () {
    $this->get('/settings')
        ->assertRedirect('/login');
});
