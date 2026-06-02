<?php

declare(strict_types=1);

use App\Billing\BillingPaymentService;
use App\Billing\CheckoutSessionDto;
use App\Billing\Gateway\FakeSubscriptionCheckoutGateway;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Data\Billing\CreditPackDto;
use App\Models\User;
use App\Services\Billing\CreditPackRepository;

beforeEach(function () {
    app()->bind(SubscriptionCheckoutGateway::class, FakeSubscriptionCheckoutGateway::class);

    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small_test');
});

it('creates credit pack checkout for user', function () {
    $user = User::factory()->create();
    $pack = app(CreditPackRepository::class)->findOrFail('small');

    $checkout = app(BillingPaymentService::class)->createCreditPackCheckout(
        user: $user,
        pack: $pack,
        successUrl: 'https://example.test/billing/credits/success',
        cancelUrl: 'https://example.test/billing/credits/cancel',
    );

    expect($checkout)->toBeInstanceOf(CheckoutSessionDto::class);
    expect($checkout->url)->not->toBeEmpty();
});

it('rejects credit pack with no stripe price id', function () {
    $user = User::factory()->create();
    $pack = new CreditPackDto(
        key: 'small',
        label: '500 Credits',
        credits: 500,
        stripePriceId: null,
        description: 'Test',
    );

    app(BillingPaymentService::class)->createCreditPackCheckout(
        user: $user,
        pack: $pack,
        successUrl: 'https://example.test/success',
        cancelUrl: 'https://example.test/cancel',
    );
})->throws(InvalidArgumentException::class);
