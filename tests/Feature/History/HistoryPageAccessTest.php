<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guest from history page to login', function () {
    $this->get('/history')
        ->assertRedirect('/login');
});

it('allows authenticated user to access history page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('Conversion History');
});
