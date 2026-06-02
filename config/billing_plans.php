<?php

return [
    'plans' => [
        'free' => [
            'label' => 'Free',
            'stripe_price_id' => null,
            'monthly_credits' => 50,
            'is_paid' => false,
        ],
        'pro' => [
            'label' => 'Pro',
            'stripe_price_id' => env('STRIPE_PRO_PRICE_ID'),
            'monthly_credits' => 1000,
            'is_paid' => true,
        ],
        'max' => [
            'label' => 'Max',
            'stripe_price_id' => env('STRIPE_MAX_PRICE_ID'),
            'monthly_credits' => 5000,
            'is_paid' => true,
        ],
    ],
];
