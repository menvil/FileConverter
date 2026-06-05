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

it('renders complete settings page for authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'Alex Johnson',
        'email' => 'alex@example.com',
    ]);

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Account Settings')
        ->assertSee('Alex Johnson')
        ->assertSee('alex@example.com')
        ->assertSee('Conversion Preferences');
});

it('does not expose billing api or device settings on minimal settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk()
        ->assertDontSee('API Keys')
        ->assertDontSee('Invoices')
        ->assertDontSee('Devices')
        ->assertDontSee('Two-factor authentication');
});
