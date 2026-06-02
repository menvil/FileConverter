<?php

use App\Billing\BillingPaymentService;
use App\Billing\BillingPlanRepository;
use App\Billing\CheckoutSessionDto;
use App\Billing\Exceptions\CannotCheckoutFreePlanException;
use App\Billing\Gateway\FakeSubscriptionCheckoutGateway;
use App\Billing\Gateway\SubscriptionCheckoutGateway;
use App\Models\User;

beforeEach(function () {
    app()->bind(SubscriptionCheckoutGateway::class, FakeSubscriptionCheckoutGateway::class);
});

it('creates subscription checkout for paid plan', function () {
    $user = User::factory()->create();
    $plan = app(BillingPlanRepository::class)->findOrFail('pro');

    $service = app(BillingPaymentService::class);

    $checkout = $service->createSubscriptionCheckout(
        user: $user,
        plan: $plan,
        successUrl: 'https://example.test/billing/success',
        cancelUrl: 'https://example.test/billing/cancel',
    );

    expect($checkout)->toBeInstanceOf(CheckoutSessionDto::class);
    expect($checkout->url)->not->toBeEmpty();
});

it('does not create subscription checkout for free plan', function () {
    $user = User::factory()->create();
    $plan = app(BillingPlanRepository::class)->findOrFail('free');

    app(BillingPaymentService::class)->createSubscriptionCheckout(
        user: $user,
        plan: $plan,
        successUrl: 'https://example.test/success',
        cancelUrl: 'https://example.test/cancel',
    );
})->throws(CannotCheckoutFreePlanException::class);
