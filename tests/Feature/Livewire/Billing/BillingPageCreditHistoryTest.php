<?php

declare(strict_types=1);

use App\Contracts\Billing\CreditLedger;
use App\Livewire\Billing\BillingPage;
use App\Models\User;
use Livewire\Livewire;

it('shows credit transaction history', function () {
    $user = User::factory()->create();

    app(CreditLedger::class)->grant($user, 50, 'registration_grant');
    app(CreditLedger::class)->spend($user, 2, 'conversion_completed');

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Credit history')
        ->assertSee('Registration grant')
        ->assertSee('Conversion completed')
        ->assertSee('+50')
        ->assertSee('-2');
});

it('shows empty state when user has no credit transactions', function () {
    $user = User::factory()->create([
        'plan' => \App\Enums\Plan::Free,
    ]);

    \App\Models\CreditTransaction::where('user_id', $user->id)->delete();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('No credit transactions yet');
});

it('does not show another users credit transactions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    app(CreditLedger::class)->grant($other, 999, 'other_user_grant');

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertDontSee('other_user_grant');
});
