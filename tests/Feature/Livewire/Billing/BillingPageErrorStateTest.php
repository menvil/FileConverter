<?php

declare(strict_types=1);

use App\Livewire\Billing\BillingPage;
use App\Models\User;
use Livewire\Livewire;

it('shows validation error for invalid subscription checkout plan', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('startSubscriptionCheckout', 'invalid-plan')
        ->assertHasErrors(['plan']);
});

it('shows validation error for invalid credit pack', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('buyCreditPack', 'invalid-pack-xyz')
        ->assertHasErrors(['pack']);
});

it('shows manage billing section', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Manage billing')
        ->assertSee('Invoices');
});

it('shows portal placeholder when portal is not configured', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('openCustomerPortal')
        ->assertHasErrors(['portal']);
});
