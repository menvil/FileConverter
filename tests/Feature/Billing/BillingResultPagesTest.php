<?php

use App\Models\User;

it('renders billing success page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing/success')
        ->assertOk()
        ->assertSee('Billing successful');
});

it('renders billing cancel page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing/cancel')
        ->assertOk()
        ->assertSee('Billing cancelled');
});

it('requires auth to view billing success page', function () {
    $this->get('/billing/success')
        ->assertRedirect('/login');
});

it('requires auth to view billing cancel page', function () {
    $this->get('/billing/cancel')
        ->assertRedirect('/login');
});
