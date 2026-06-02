<?php

namespace App\Billing;

final readonly class CheckoutSessionDto
{
    public function __construct(
        public string $url,
    ) {}
}
