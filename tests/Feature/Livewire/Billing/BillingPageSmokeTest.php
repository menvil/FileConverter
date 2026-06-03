<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\Plan;
use App\Livewire\Billing\BillingPage;
use App\Models\User;
use Livewire\Livewire;

it('renders complete billing page for authenticated user', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    app(CreditLedger::class)->grant($user, 50, 'registration_grant');

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Billing')
        ->assertSee('Current plan')
        ->assertSee('Credits balance')
        ->assertSee('Plan limits')
        ->assertSee('Available plans')
        ->assertSee('Buy credits')
        ->assertSee('Credit history')
        ->assertSee('Manage billing');
});

it('guest cannot access billing page', function () {
    $this->get('/billing')
        ->assertRedirect('/login');
});

it('does not show another users credit transactions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    app(CreditLedger::class)->grant($other, 999, 'other_user_grant');

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertDontSee('other_user_grant');
});
