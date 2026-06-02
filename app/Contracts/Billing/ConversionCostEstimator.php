<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Data\Credits\CreditCost;
use App\Models\FileRecord;
use App\Support\Converters\Contracts\Converter;

interface ConversionCostEstimator
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function estimate(FileRecord $file, Converter $converter, array $options = []): CreditCost;
}
