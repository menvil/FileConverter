<?php

declare(strict_types=1);

function fakeStripeCheckoutCompletedEvent(array $params = []): array
{
    $eventId = $params['event_id'] ?? 'evt_fake_'.uniqid();
    $checkoutSessionId = $params['checkout_session_id'] ?? 'cs_fake_'.uniqid();
    $paymentIntentId = $params['payment_intent_id'] ?? null;
    $customerId = $params['customer_id'] ?? null;
    $userId = $params['user_id'] ?? null;
    $priceId = $params['price_id'] ?? null;
    $packKey = $params['pack_key'] ?? null;
    $packCredits = $params['pack_credits'] ?? null;

    return [
        'id' => $eventId,
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $checkoutSessionId,
                'payment_intent' => $paymentIntentId,
                'customer' => $customerId,
                'metadata' => array_filter([
                    'user_id' => $userId !== null ? (string) $userId : null,
                    'pack_key' => $packKey,
                    'pack_credits' => $packCredits !== null ? (string) $packCredits : null,
                    'price_id' => $priceId,
                ], fn ($v) => $v !== null),
            ],
        ],
    ];
}
