<?php

declare(strict_types=1);

use App\Models\User;

it('shows billing success notice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing?checkout=success')
        ->assertOk()
        ->assertSee('Payment received');
});

it('shows billing cancelled notice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing?checkout=cancelled')
        ->assertOk()
        ->assertSee('Checkout was cancelled');
});

it('ignores unknown checkout query value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing?checkout=unknown')
        ->assertOk()
        ->assertDontSee('Payment received')
        ->assertDontSee('Checkout was cancelled');
});
