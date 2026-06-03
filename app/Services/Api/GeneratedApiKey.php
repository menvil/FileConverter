<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\ApiKey;

final readonly class GeneratedApiKey
{
    public function __construct(
        public ApiKey $apiKey,
        public string $plainToken,
    ) {}
}
