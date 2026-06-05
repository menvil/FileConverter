<?php

use App\Models\User;

it('renders toast notification container in app layout', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('toast-region', false);
});
