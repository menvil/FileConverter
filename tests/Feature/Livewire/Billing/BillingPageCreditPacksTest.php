<?php

declare(strict_types=1);

use App\Billing\Gateway\CreditPackCheckoutGateway;
use App\Billing\Gateway\FakeCreditPackCheckoutGateway;
use App\Livewire\Billing\BillingPage;
use App\Models\User;
use Livewire\Livewire;

it('shows available credit packs on billing page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Buy credits')
        ->assertSee('500 Credits')
        ->assertSee('2,000 Credits');
});

it('starts credit pack checkout', function () {
    app()->bind(CreditPackCheckoutGateway::class, FakeCreditPackCheckoutGateway::class);
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small_test');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('buyCreditPack', 'small')
        ->assertRedirect();
});

it('rejects invalid credit pack', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('buyCreditPack', 'invalid-pack')
        ->assertHasErrors(['pack']);
});
