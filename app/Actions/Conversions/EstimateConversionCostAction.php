<?php

declare(strict_types=1);

namespace App\Actions\Conversions;

use App\Contracts\Billing\ConversionCostEstimator;
use App\Data\Credits\CreditCost;
use App\Models\FileRecord;
use App\Support\Converters\Contracts\Converter;

final class EstimateConversionCostAction
{
    public function __construct(
        private readonly ConversionCostEstimator $estimator,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function handle(FileRecord $file, Converter $converter, array $options = []): CreditCost
    {
        return $this->estimator->estimate($file, $converter, $options);
    }
}
