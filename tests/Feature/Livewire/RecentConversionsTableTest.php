<?php

declare(strict_types=1);

use App\Livewire\RecentConversionsTable;
use App\Models\User;
use Livewire\Livewire;

it('renders recent conversions table component', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Recent Conversions');
});

it('renders recent conversions section on dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Recent Conversions');
});

it('shows empty state when user has no conversions', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('No conversions yet')
        ->assertSee('Upload a file to start converting');
});
