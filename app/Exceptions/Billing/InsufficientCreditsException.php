<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use App\Exceptions\DomainExceptionContract;

final class InsufficientCreditsException extends \DomainException implements DomainExceptionContract
{
    private int $required = 0;

    private int $available = 0;

    public static function forCost(int $required, int $available): self
    {
        $e = new self("Not enough credits. Required: {$required}, available: {$available}.");
        $e->required = $required;
        $e->available = $available;

        return $e;
    }

    /** @deprecated Use forCost() */
    public static function make(int $required, int $available): self
    {
        return self::forCost($required, $available);
    }

    public function code(): string
    {
        return 'insufficient_credits';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return [
            'required' => $this->required,
            'available' => $this->available,
        ];
    }
}
