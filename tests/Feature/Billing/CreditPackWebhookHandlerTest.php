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
