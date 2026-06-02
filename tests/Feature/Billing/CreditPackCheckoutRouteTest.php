<?php

declare(strict_types=1);

use App\Billing\Gateway\CreditPackCheckoutGateway;
use App\Billing\Gateway\FakeCreditPackCheckoutGateway;
use App\Billing\Gateway\FakeSubscriptionCheckoutGateway;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Models\User;

beforeEach(function () {
    app()->bind(SubscriptionCheckoutGateway::class, FakeSubscriptionCheckoutGateway::class);
    app()->bind(CreditPackCheckoutGateway::class, FakeCreditPackCheckoutGateway::class);

    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small_test');
});

it('requires auth for credit pack checkout', function () {
    $this->post(route('billing.credits.checkout', ['pack' => 'small']))
        ->assertRedirect(route('login'));
});

it('starts credit pack checkout for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.credits.checkout', ['pack' => 'small']))
        ->assertRedirect('https://checkout.stripe.test/fake-credit-pack-session');
});

it('rejects unknown credit pack checkout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.credits.checkout', ['pack' => 'unknown']))
        ->assertNotFound();
});
