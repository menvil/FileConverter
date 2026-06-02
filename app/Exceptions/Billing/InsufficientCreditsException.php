<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use DomainException;

final class InsufficientCreditsException extends DomainException
{
    public static function make(int $required, int $available): self
    {
        return new self("Not enough credits. Required: {$required}, available: {$available}.");
    }
}
