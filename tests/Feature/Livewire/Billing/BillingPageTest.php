<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Enums\Plan;
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
        'plan' => Plan::Pro,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Current plan')
        ->assertSee('Pro');
});

it('shows free plan for free user on billing page', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Current plan')
        ->assertSee('Free');
});

it('shows current credits balance on billing page', function () {
    $user = User::factory()->create();

    app(CreditLedger::class)->grant(
        user: $user,
        amount: 500,
        reason: 'test_grant',
    );

    $balance = app(CreditLedger::class)->balance($user);

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
        'plan' => Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Plan limits')
        ->assertSee('Max file size')
        ->assertSee('API access');
});

it('shows correct free plan limit values', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('25 MB')
        ->assertSee('Disabled');
});

it('shows available billing plans', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Available plans')
        ->assertSee('Free')
        ->assertSee('Pro')
        ->assertSee('Max');
});

it('highlights the current plan', function () {
    $user = User::factory()->create([
        'plan' => Plan::Pro,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Current plan')
        ->assertSee('Pro');
});

it('does not show upgrade button for current plan', function () {
    $user = User::factory()->create([
        'plan' => Plan::Pro,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertDontSee('Upgrade to Pro');
});
