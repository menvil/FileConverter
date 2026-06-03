<?php

declare(strict_types=1);

namespace App\Data\Api;

final readonly class EstimateResult
{
    public function __construct(
        public int $amount,
        public array $breakdown,
        public bool $hasEnoughCredits,
    ) {}
}
