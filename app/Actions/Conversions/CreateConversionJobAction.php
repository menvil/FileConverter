<?php

declare(strict_types=1);

namespace App\Actions\Conversions;

use App\Contracts\Billing\CreditLedger;
use App\Enums\ConversionCreditChargeStatus;
use App\Enums\ConversionStatus;
use App\Enums\FileFormat;
use App\Exceptions\Billing\InsufficientCreditsException;
use App\Jobs\ProcessConversionJob;
use App\Models\ConversionCreditCharge;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\Exceptions\UnsupportedFormatException;
use Illuminate\Support\Facades\DB;

final class CreateConversionJobAction
{
    public function __construct(
        private readonly ConverterRegistry $converterRegistry,
        private readonly EstimateConversionCostAction $estimateCost,
        private readonly CreditLedger $creditLedger,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function handle(
        User $user,
        FileRecord $sourceFile,
        string $targetFormat,
        array $options = [],
    ): ConversionJob {
        try {
            $sourceFormat = FileFormat::normalize($sourceFile->extension);
            $normalizedTarget = FileFormat::normalize($targetFormat);
        } catch (UnsupportedFormatException $exception) {
            throw UnsupportedConversionException::forPair(
                $sourceFile->extension,
                $targetFormat,
                $exception,
            );
        }

        $converter = $this->converterRegistry->find($sourceFormat, $normalizedTarget);

        if ($converter === null) {
            throw UnsupportedConversionException::forPair($sourceFormat, $normalizedTarget);
        }

        $normalizedOptions = $converter->validateOptions($options);

        $cost = $this->estimateCost->handle($sourceFile, $converter, $normalizedOptions);

        $balance = $this->creditLedger->balance($user);

        if ($balance < $cost->amount) {
            throw InsufficientCreditsException::forCost(
                required: $cost->amount,
                available: $balance,
            );
        }

        // Wrap job + charge creation in a transaction so a failed charge insert
        // cannot leave an orphan queued job. Dispatch happens after commit so
        // the worker never picks up a job whose charge record does not exist.
        $job = DB::transaction(function () use ($user, $sourceFile, $sourceFormat, $normalizedTarget, $normalizedOptions, $cost) {
            $job = ConversionJob::create([
                'user_id' => $user->id,
                'source_file_id' => $sourceFile->id,
                'source_format' => $sourceFormat,
                'target_format' => $normalizedTarget,
                // Driver-registry key (e.g. "png_to_jpg") — distinct from the catalog
                // Converter::key() ("png:jpg"). ConverterDriverRegistry::findOrFail()
                // resolves drivers on this value, so it must match driver keys.
                'converter_key' => "{$sourceFormat}_to_{$normalizedTarget}",
                'options_json' => $normalizedOptions,
                'status' => ConversionStatus::Queued,
                'progress' => 0,
            ]);

            ConversionCreditCharge::create([
                'user_id' => $user->id,
                'conversion_job_id' => $job->id,
                'estimated_amount' => $cost->amount,
                'captured_amount' => 0,
                'refunded_amount' => 0,
                'status' => ConversionCreditChargeStatus::Estimated,
                'breakdown_json' => $cost->breakdown,
            ]);

            return $job;
        });

        ProcessConversionJob::dispatch($job->id);

        return $job;
    }
}
