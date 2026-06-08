<?php

declare(strict_types=1);

namespace App\Exceptions;

abstract class DomainException extends \DomainException implements DomainExceptionContract
{
    /** @return array<string, mixed> */
    public function details(): array
    {
        return [];
    }
}
