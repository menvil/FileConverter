<?php

use App\Models\User;

it('renders accessible label for dashboard file upload area', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('File upload area', false)
        ->assertSeeHtml('aria-label');
});

it('renders accessible labels for filter inputs on history page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/history')
        ->assertOk()
        ->assertSeeHtml('label')
        ->assertSeeHtml('for="history-search"');
});

it('renders accessible labels for filter inputs on dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeHtml('for="recent-search"');
});
