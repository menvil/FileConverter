<?php

it('renders dashboard inside the app layout', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('ConvertAI')
        ->assertSee('Privacy Policy');
});

it('renders user dropdown shell in dashboard header', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Alex Johnson')
        ->assertSee('Storage Usage')
        ->assertSee('Credits')
        ->assertSee('Billing')
        ->assertSee('Settings')
        ->assertSee('Upgrade to Max')
        ->assertSee('x-data', false);
});

it('renders footer help cards on dashboard', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Help Center')
        ->assertSee('Contact Support')
        ->assertSee('Refer a Friend');
});

it('renders dashboard UI skeleton', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Convert any file')
        ->assertSee('File')
        ->assertSee('Format')
        ->assertSee('Settings')
        ->assertSee('Convert')
        ->assertSee('Recent Conversions')
        ->assertSee('Marketing Report.pdf');
});
