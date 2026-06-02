<?php

return [
    'plans' => [
        'free' => [
            'features' => [
                'api_access' => false,
                'batch_conversion' => false,
                'priority_queue' => false,
                'ocr' => false,
                'video_conversion' => false,
            ],
            'limits' => [
                'max_file_size_mb' => 25,
                'storage_mb' => 250,
                'retention_days' => 1,
                'monthly_credits' => 50,
            ],
        ],
        'pro' => [
            'features' => [
                'api_access' => true,
                'batch_conversion' => true,
                'priority_queue' => false,
                'ocr' => true,
                'video_conversion' => false,
            ],
            'limits' => [
                'max_file_size_mb' => 250,
                'storage_mb' => 10000,
                'retention_days' => 30,
                'monthly_credits' => 1000,
            ],
        ],
        'max' => [
            'features' => [
                'api_access' => true,
                'batch_conversion' => true,
                'priority_queue' => true,
                'ocr' => true,
                'video_conversion' => true,
            ],
            'limits' => [
                'max_file_size_mb' => 2000,
                'storage_mb' => 100000,
                'retention_days' => 90,
                'monthly_credits' => 5000,
            ],
        ],
    ],
];
