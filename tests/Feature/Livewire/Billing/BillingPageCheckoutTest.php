<?php

declare(strict_types=1);

use App\Billing\Gateway\FakeSubscriptionCheckoutGateway;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Enums\Plan;
use App\Livewire\Billing\BillingPage;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    app()->bind(SubscriptionCheckoutGateway::class, FakeSubscriptionCheckoutGateway::class);
});

it('starts subscription checkout for paid plan', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('startSubscriptionCheckout', 'pro')
        ->assertRedirect('https://checkout.stripe.test/fake-session');
});

it('rejects checkout for free plan', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('startSubscriptionCheckout', 'free')
        ->assertHasErrors(['plan']);
});

it('rejects checkout for current plan', function () {
    $user = User::factory()->create([
        'plan' => Plan::Pro,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('startSubscriptionCheckout', 'pro')
        ->assertHasErrors(['plan']);
});
