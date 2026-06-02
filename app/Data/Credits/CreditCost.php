<?php

declare(strict_types=1);

namespace App\Data\Credits;

final readonly class CreditCost
{
    public function __construct(
        public int $amount,
        public array $breakdown,
    ) {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Credit cost amount cannot be negative.');
        }
    }
}
