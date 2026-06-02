<?php

namespace App\Billing\Exceptions;

use RuntimeException;

final class UnknownBillingPlanException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("Unknown billing plan key: {$key}");
    }
}
