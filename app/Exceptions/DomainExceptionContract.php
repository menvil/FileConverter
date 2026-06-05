<?php

declare(strict_types=1);

namespace App\Exceptions;

interface DomainExceptionContract
{
    public function code(): string;

    /** @return array<string, mixed> */
    public function details(): array;
}
