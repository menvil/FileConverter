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
        ->assertSee('Storage Usage')
        ->assertSee('Credits')
        ->assertSee('Billing')
        ->assertSee('Settings')
        ->assertSee('Upgrade to Max')
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

it('renders dashboard UI skeleton', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Convert any file')
        ->assertSee('File')
        ->assertSee('Format')
        ->assertSee('Settings')
        ->assertSee('Convert')
        ->assertSee('Recent Conversions')
        ->assertSee('Marketing Report.pdf');
});
