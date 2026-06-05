<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use App\Exceptions\DomainException;

final class InsufficientCreditsException extends DomainException
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

    /**
     * @deprecated since v0.1.0, will be removed in v0.2.0. Use forCost() instead.
     */
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
