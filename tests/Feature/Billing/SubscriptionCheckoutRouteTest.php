<?php

use App\Billing\Gateway\FakeSubscriptionCheckoutGateway;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Models\User;

beforeEach(function () {
    app()->bind(SubscriptionCheckoutGateway::class, FakeSubscriptionCheckoutGateway::class);
});

it('requires auth to start subscription checkout', function () {
    $this->post('/billing/checkout/pro')
        ->assertRedirect('/login');
});

it('starts subscription checkout for pro plan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/billing/checkout/pro')
        ->assertRedirect('https://checkout.stripe.test/fake-session');
});

it('rejects checkout for unknown plan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/billing/checkout/unknown')
        ->assertStatus(404);
});

it('rejects checkout for free plan', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/billing/checkout/free')
        ->assertStatus(422);
});
