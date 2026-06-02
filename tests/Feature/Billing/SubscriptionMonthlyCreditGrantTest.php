<?php

use App\Billing\Webhooks\SubscriptionWebhookHandler;
use App\Contracts\Billing\CreditLedger;
use App\Models\CreditTransaction;
use App\Models\User;

it('grants monthly subscription credits once for paid invoice', function () {
    $user = User::factory()->create(['plan' => 'pro']);

    $handler = app(SubscriptionWebhookHandler::class);

    $handler->handleInvoicePaid(
        user: $user,
        planKey: 'pro',
        invoiceId: 'in_test_123',
        payload: ['event_id' => 'evt_test_1'],
    );

    $handler->handleInvoicePaid(
        user: $user,
        planKey: 'pro',
        invoiceId: 'in_test_123',
        payload: ['event_id' => 'evt_test_1_duplicate'],
    );

    expect(app(CreditLedger::class)->balance($user))->toBe(1000);
});

it('does not grant credits for free plan invoice', function () {
    $user = User::factory()->create(['plan' => 'free']);

    app(SubscriptionWebhookHandler::class)->handleInvoicePaid(
        user: $user,
        planKey: 'free',
        invoiceId: 'in_free_test',
        payload: [],
    );

    expect(app(CreditLedger::class)->balance($user))->toBe(0);
});

it('stores invoice id in credit transaction metadata', function () {
    $user = User::factory()->create(['plan' => 'pro']);

    app(SubscriptionWebhookHandler::class)->handleInvoicePaid(
        user: $user,
        planKey: 'pro',
        invoiceId: 'in_meta_test',
        payload: [],
    );

    $transaction = CreditTransaction::query()
        ->where('user_id', $user->id)
        ->where('reason', 'subscription_monthly_grant')
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction->metadata_json['stripe_invoice_id'])->toBe('in_meta_test');
    expect($transaction->metadata_json['plan'])->toBe('pro');
});
