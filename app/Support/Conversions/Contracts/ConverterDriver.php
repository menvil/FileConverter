<?php

declare(strict_types=1);

namespace App\Support\Conversions\Contracts;

use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;

interface ConverterDriver
{
    public function key(): string;

    public function convert(ConversionContext $context): ConversionResult;
}
