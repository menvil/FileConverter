<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ConversionCostEstimator;
use App\Data\Credits\CreditCost;
use App\Data\Credits\CreditCostBreakdown;
use App\Exceptions\Billing\UnsupportedConversionCostException;
use App\Models\FileRecord;
use App\Support\Converters\Contracts\Converter;

final class ConfigDrivenConversionCostEstimator implements ConversionCostEstimator
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function estimate(FileRecord $file, Converter $converter, array $options = []): CreditCost
    {
        $source = $converter->sourceFormat();
        $target = $converter->targetFormat();

        if ($this->isImage($source) && $this->isImage($target)) {
            return $this->makeCost('image_to_image', $file, $converter);
        }

        if ($this->isImage($source) && $this->isPdf($target)) {
            return $this->makeCost('image_to_pdf', $file, $converter);
        }

        throw UnsupportedConversionCostException::forPair($source, $target);
    }

    private function makeCost(string $rule, FileRecord $file, Converter $converter): CreditCost
    {
        $base = (int) config("conversion_costs.rules.{$rule}.base", 1);

        $breakdown = new CreditCostBreakdown(
            base: $base,
            size: 0,
            features: 0,
            total: $base,
            details: [
                'rule' => $rule,
                'source_format' => $converter->sourceFormat(),
                'target_format' => $converter->targetFormat(),
                'converter_key' => $converter->key(),
                'file_size_bytes' => $file->size_bytes,
            ],
        );

        return new CreditCost(
            amount: $breakdown->total,
            breakdown: $breakdown->toArray(),
        );
    }

    private function isImage(string $format): bool
    {
        return in_array($format, config('conversion_costs.groups.image', []), true);
    }

    private function isPdf(string $format): bool
    {
        return in_array($format, config('conversion_costs.groups.pdf', []), true);
    }
}
