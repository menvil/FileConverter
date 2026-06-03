<?php

declare(strict_types=1);

use App\Livewire\Billing\BillingPage;
use App\Models\User;
use Livewire\Livewire;

it('renders billing page livewire component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Billing');
});

it('shows current user plan on billing page', function () {
    $user = User::factory()->create([
        'plan' => \App\Enums\Plan::Pro,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Current plan')
        ->assertSee('Pro');
});

it('shows free plan for free user on billing page', function () {
    $user = User::factory()->create([
        'plan' => \App\Enums\Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Current plan')
        ->assertSee('Free');
});
