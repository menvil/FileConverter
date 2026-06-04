<?php

declare(strict_types=1);

namespace App\Exceptions\Files;

use RuntimeException;

final class InvalidRetentionPolicyException extends RuntimeException
{
    public static function forInvalidDays(int|string|null $raw): self
    {
        return new self('Retention days must be a positive integer, got ['.var_export($raw, true).'].');
    }
}
