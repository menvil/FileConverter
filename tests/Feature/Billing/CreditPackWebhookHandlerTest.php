<?php

declare(strict_types=1);

use App\Billing\Webhooks\CreditPackWebhookHandler;
use App\Contracts\Billing\CreditLedger;
use App\Models\User;

it('grants credits after successful credit pack checkout', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $user = User::factory()->create();
    $balanceBefore = app(CreditLedger::class)->balance($user);

    $event = fakeStripeCheckoutCompletedEvent([
        'event_id' => 'evt_credit_pack_1',
        'checkout_session_id' => 'cs_test_1',
        'user_id' => $user->id,
        'price_id' => 'price_small',
        'pack_key' => 'small',
        'pack_credits' => 500,
        'payment_intent_id' => 'pi_test_1',
    ]);

    app(CreditPackWebhookHandler::class)
        ->handleCheckoutSessionCompleted($event);

    expect(app(CreditLedger::class)->balance($user))->toBe($balanceBefore + 500);
});

it('does not grant credits twice for duplicate credit pack checkout event', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $user = User::factory()->create();
    $balanceBefore = app(CreditLedger::class)->balance($user);

    $event = fakeStripeCheckoutCompletedEvent([
        'event_id' => 'evt_duplicate_1',
        'checkout_session_id' => 'cs_duplicate_1',
        'user_id' => $user->id,
        'pack_key' => 'small',
        'pack_credits' => 500,
        'price_id' => 'price_small',
    ]);

    app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event);
    app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event);

    expect(app(CreditLedger::class)->balance($user))->toBe($balanceBefore + 500);
});

it('does not grant credits twice for same checkout session with different event ids', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $user = User::factory()->create();
    $balanceBefore = app(CreditLedger::class)->balance($user);

    $event1 = fakeStripeCheckoutCompletedEvent([
        'event_id' => 'evt_dup_a',
        'checkout_session_id' => 'cs_same_session',
        'user_id' => $user->id,
        'pack_key' => 'small',
        'pack_credits' => 500,
        'price_id' => 'price_small',
    ]);

    $event2 = fakeStripeCheckoutCompletedEvent([
        'event_id' => 'evt_dup_b',
        'checkout_session_id' => 'cs_same_session',
        'user_id' => $user->id,
        'pack_key' => 'small',
        'pack_credits' => 500,
        'price_id' => 'price_small',
    ]);

    app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event1);
    app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event2);

    expect(app(CreditLedger::class)->balance($user))->toBe($balanceBefore + 500);
});
