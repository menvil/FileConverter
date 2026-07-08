<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Conversions\RecordConversionResultFileAction;
use App\Contracts\Billing\CreditLedger;
use App\Enums\ConversionCreditChargeStatus;
use App\Enums\ConversionStatus;
use App\Models\ConversionCreditCharge;
use App\Models\ConversionJob;
use App\Support\Conversions\ConverterDriverRegistry;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Logging\ConversionLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProcessConversionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $conversionJobId,
    ) {}

    public function handle(
        ConverterDriverRegistry $drivers,
        RecordConversionResultFileAction $recorder,
        CreditLedger $creditLedger,
        ?ConversionLogger $logger = null,
    ): void {
        $logger ??= app(ConversionLogger::class);
        $job = ConversionJob::find($this->conversionJobId);

        if ($job === null || $job->status !== ConversionStatus::Queued) {
            return;
        }

        $job->forceFill([
            'status' => ConversionStatus::Processing,
            'progress' => 10,
            'started_at' => now(),
        ])->save();

        $logger->jobStarted($job);

        try {
            $driver = $drivers->findOrFail($job->converter_key);

            $context = new ConversionContext(
                job: $job,
                sourceFile: $job->sourceFile,
                options: $job->options_json ?? [],
                outputDirectory: "conversions/results/{$job->id}",
            );

            $result = $driver->convert($context);

            $resultFile = $recorder->handle($job->user, $result, $job->guest_token);

            $job->forceFill([
                'result_file_id' => $resultFile->id,
                'status' => ConversionStatus::Completed,
                'progress' => 100,
                'completed_at' => now(),
            ])->save();

            try {
                $logger->jobCompleted($job);
            } catch (Throwable $logException) {
                report($logException);
            }

            // Capture credits separately — a billing failure must not undo a
            // successful conversion, so we report and move on rather than
            // letting the exception propagate to the failure catch block below.
            try {
                $this->captureCredits($job, $creditLedger);
            } catch (Throwable $captureException) {
                report($captureException);
            }
        } catch (Throwable $exception) {
            $job->forceFill([
                'status' => ConversionStatus::Failed,
                'error_code' => Str::snake(class_basename($exception)),
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ])->save();

            $logger->jobFailed($job);

            $this->markChargeFailed($job);

            report($exception);
        }
    }

    private function captureCredits(ConversionJob $job, CreditLedger $creditLedger): void
    {
        if ($job->user === null) {
            return;
        }

        $charge = $job->creditCharge;

        if ($charge === null) {
            return;
        }

        DB::transaction(function () use ($job, $charge, $creditLedger) {
            // Lock the charge row and verify it is still in the expected state
            // before spending, so duplicate executions are idempotent.
            $lockedCharge = ConversionCreditCharge::lockForUpdate()->find($charge->id);

            if ($lockedCharge === null || $lockedCharge->status !== ConversionCreditChargeStatus::Estimated) {
                return;
            }

            $creditLedger->spend(
                user: $job->user,
                amount: $lockedCharge->estimated_amount,
                reason: 'conversion_completed',
                meta: [
                    'conversion_job_id' => $job->id,
                    'converter_key' => $job->converter_key,
                ],
            );

            $lockedCharge->forceFill([
                'captured_amount' => $lockedCharge->estimated_amount,
                'status' => ConversionCreditChargeStatus::Captured,
            ])->save();
        });
    }

    private function markChargeFailed(ConversionJob $job): void
    {
        $charge = $job->creditCharge;

        if ($charge === null) {
            return;
        }

        $charge->forceFill([
            'status' => ConversionCreditChargeStatus::Failed,
        ])->save();
    }
}
