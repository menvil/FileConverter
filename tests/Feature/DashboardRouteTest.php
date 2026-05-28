<?php

it('renders dashboard inside the app layout', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('ConvertAI')
        ->assertSee('File Converter Dashboard')
        ->assertSee('Privacy Policy');
});

it('renders user dropdown shell in dashboard header', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Account')
        ->assertSee('Credits')
        ->assertSee('Billing')
        ->assertSee('Settings')
        ->assertSee('x-data', false);
});

it('renders footer help cards on dashboard', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Help Center')
        ->assertSee('Contact Support')
        ->assertSee('Refer a Friend');
});
