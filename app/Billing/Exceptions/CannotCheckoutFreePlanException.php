<?php

namespace App\Billing\Exceptions;

use RuntimeException;

final class CannotCheckoutFreePlanException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Cannot create checkout for a free plan.');
    }
}
