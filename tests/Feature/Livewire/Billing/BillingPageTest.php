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

it('shows current credits balance on billing page', function () {
    $user = User::factory()->create();

    app(\App\Contracts\Billing\CreditLedger::class)->grant(
        user: $user,
        amount: 500,
        reason: 'test_grant',
    );

    $balance = app(\App\Contracts\Billing\CreditLedger::class)->balance($user);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Credits balance')
        ->assertSee((string) $balance);
});

it('shows zero credits balance when user has no credits', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Credits balance')
        ->assertSee('0');
});

it('shows current plan limits on billing page', function () {
    $user = User::factory()->create([
        'plan' => \App\Enums\Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Plan limits')
        ->assertSee('Max file size')
        ->assertSee('API access');
});

it('shows correct free plan limit values', function () {
    $user = User::factory()->create([
        'plan' => \App\Enums\Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('25 MB')
        ->assertSee('Disabled');
});
