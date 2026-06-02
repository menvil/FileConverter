<?php

use App\Billing\Exceptions\UnknownBillingPlanException;
use App\Billing\Webhooks\SubscriptionWebhookHandler;
use App\Models\User;

it('updates user plan when subscription is activated', function () {
    $user = User::factory()->create(['plan' => 'free']);

    app(SubscriptionWebhookHandler::class)->handleSubscriptionActivated(
        user: $user,
        planKey: 'pro',
        payload: ['stripe_subscription_id' => 'sub_test_123'],
    );

    expect($user->fresh()->plan->value)->toBe('pro');
});

it('rejects unknown plan during subscription activation', function () {
    $user = User::factory()->create(['plan' => 'free']);

    app(SubscriptionWebhookHandler::class)->handleSubscriptionActivated($user, 'unknown');
})->throws(UnknownBillingPlanException::class);

it('updates user plan to max when subscription is activated with max plan', function () {
    $user = User::factory()->create(['plan' => 'free']);

    app(SubscriptionWebhookHandler::class)->handleSubscriptionActivated(
        user: $user,
        planKey: 'max',
        payload: [],
    );

    expect($user->fresh()->plan->value)->toBe('max');
});

it('downgrades user to free when subscription is cancelled', function () {
    $user = User::factory()->create(['plan' => 'pro']);

    app(SubscriptionWebhookHandler::class)->handleSubscriptionCancelled(
        user: $user,
        payload: ['stripe_subscription_id' => 'sub_test_123'],
    );

    expect($user->fresh()->plan->value)->toBe('free');
});
