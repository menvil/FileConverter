<?php

declare(strict_types=1);

return [
    'rules' => [
        'image_to_image' => [
            'base' => 1,
            'size' => 0,
            'features' => [],
        ],
        'image_to_pdf' => [
            'base' => 2,
            'size' => 0,
            'features' => [],
        ],
    ],

    'groups' => [
        'image' => ['png', 'jpg', 'jpeg', 'webp'],
        'pdf' => ['pdf'],
    ],
];
