<?php

namespace App\Support\Converters;

use App\Support\Converters\Contracts\Converter;

final class ConverterRegistry
{
    /** @param list<Converter> $converters */
    public function __construct(
        private readonly array $converters = [],
    ) {}
}
