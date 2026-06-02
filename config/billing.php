<?php

return [
    'credit_packs' => [
        'small' => [
            'label' => '500 Credits',
            'credits' => 500,
            'stripe_price_id' => env('STRIPE_CREDIT_PACK_SMALL_PRICE_ID'),
            'description' => 'Good for occasional conversions.',
        ],
        'medium' => [
            'label' => '2,000 Credits',
            'credits' => 2000,
            'stripe_price_id' => env('STRIPE_CREDIT_PACK_MEDIUM_PRICE_ID'),
            'description' => 'Best for regular usage.',
        ],
        'large' => [
            'label' => '10,000 Credits',
            'credits' => 10000,
            'stripe_price_id' => env('STRIPE_CREDIT_PACK_LARGE_PRICE_ID'),
            'description' => 'For heavy users and API workloads.',
        ],
    ],
];
