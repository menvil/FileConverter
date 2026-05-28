<?php

use App\Enums\Plan;
use App\Models\User;

it('shows authenticated user data in dashboard header', function () {
    $user = User::factory()->create([
        'name' => 'Alex Johnson',
        'email' => 'alex@example.com',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Alex Johnson')
        ->assertSee('alex@example.com');
});

it('shows user plan in dashboard header dropdown', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Free');
});

it('shows pro plan badge for pro users', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Pro');
});
