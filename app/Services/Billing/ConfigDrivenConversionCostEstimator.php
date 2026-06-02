<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ConversionCostEstimator;
use App\Data\Credits\CreditCost;
use App\Models\FileRecord;
use App\Support\Converters\Contracts\Converter;

final class ConfigDrivenConversionCostEstimator implements ConversionCostEstimator
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function estimate(FileRecord $file, Converter $converter, array $options = []): CreditCost
    {
        throw new \LogicException('Not implemented yet.');
    }
}
