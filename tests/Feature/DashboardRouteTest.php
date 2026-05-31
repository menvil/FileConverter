<?php

use App\Models\User;

it('redirects guest from dashboard to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

it('allows authenticated user to access dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Convert any file');
});

it('renders dashboard converter on dashboard page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Drop your file here');
});

it('renders dashboard inside the app layout', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('ConvertAI')
        ->assertSee('Privacy Policy');
});

it('renders user dropdown shell in dashboard header', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Billing')
        ->assertSee('Settings')
        ->assertSee('x-data', false);
});

it('renders footer help cards on dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Help Center')
        ->assertSee('Contact Support')
        ->assertSee('Refer a Friend');
});
