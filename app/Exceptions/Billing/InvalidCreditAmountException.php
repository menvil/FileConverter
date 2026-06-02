<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use DomainException;

final class InvalidCreditAmountException extends DomainException
{
    public static function becauseAmountMustBePositive(): self
    {
        return new self('Credit amount must be a positive integer.');
    }
}
