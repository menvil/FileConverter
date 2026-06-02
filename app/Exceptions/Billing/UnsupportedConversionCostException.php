<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use DomainException;

final class UnsupportedConversionCostException extends DomainException
{
    public static function forPair(string $source, string $target): self
    {
        return new self("Cannot estimate cost for unsupported conversion: {$source} → {$target}.");
    }
}
