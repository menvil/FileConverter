<?php

namespace App\Billing;

final readonly class BillingPlanDto
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $stripePriceId,
        public int $monthlyCredits,
        public bool $isPaid,
    ) {}
}
