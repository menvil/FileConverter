<?php

declare(strict_types=1);

use App\Models\User;

it('renders credit pack checkout success page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('billing.credits.success'))
        ->assertOk()
        ->assertSee('Credits purchase successful');
});

it('renders credit pack checkout cancel page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('billing.credits.cancel'))
        ->assertOk()
        ->assertSee('Credits purchase cancelled');
});

it('requires auth for credits success page', function () {
    $this->get(route('billing.credits.success'))
        ->assertRedirect(route('login'));
});

it('requires auth for credits cancel page', function () {
    $this->get(route('billing.credits.cancel'))
        ->assertRedirect(route('login'));
});
