<?php

declare(strict_types=1);

namespace App\Support\Api;

final readonly class MappedApiError
{
    public function __construct(
        public string $code,
        public string $message,
        public int $status,
        public array $details = [],
    ) {}
}
