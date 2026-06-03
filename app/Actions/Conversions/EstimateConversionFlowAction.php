<?php

declare(strict_types=1);

namespace App\Actions\Conversions;

use App\Contracts\Billing\CreditLedger;
use App\Data\Api\EstimateResult;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Api\ApiOwnershipGuard;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\ConverterRegistry;

final class EstimateConversionFlowAction
{
    public function __construct(
        private readonly ConverterRegistry $registry,
        private readonly EstimateConversionCostAction $estimateCost,
        private readonly CreditLedger $creditLedger,
        private readonly ApiOwnershipGuard $ownershipGuard,
    ) {}

    public function handle(
        User $user,
        int $fileId,
        string $targetFormat,
        array $options = [],
    ): EstimateResult {
        $file = FileRecord::findOrFail($fileId);

        $this->ownershipGuard->ensureFileOwner($user, $file);

        $converter = $this->registry->find($file->extension, $targetFormat);

        if ($converter === null) {
            throw UnsupportedConversionException::forPair($file->extension, $targetFormat);
        }

        $normalizedOptions = $converter->validateOptions($options);

        $cost = $this->estimateCost->handle($file, $converter, $normalizedOptions);

        $balance = $this->creditLedger->balance($user);

        return new EstimateResult(
            amount: $cost->amount,
            breakdown: $cost->breakdown,
            hasEnoughCredits: $balance >= $cost->amount,
        );
    }
}
