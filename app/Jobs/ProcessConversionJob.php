<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Conversions\RecordConversionResultFileAction;
use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use App\Support\Conversions\ConverterDriverRegistry;
use App\Support\Conversions\DTO\ConversionContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    ): void {
        $job = ConversionJob::find($this->conversionJobId);

        if ($job === null || $job->status !== ConversionStatus::Queued) {
            return;
        }

        $job->forceFill([
            'status' => ConversionStatus::Processing,
            'progress' => 10,
            'started_at' => now(),
        ])->save();

        try {
            $driver = $drivers->findOrFail($job->converter_key);

            $context = new ConversionContext(
                job: $job,
                sourceFile: $job->sourceFile,
                options: $job->options_json ?? [],
                outputDirectory: "conversions/results/{$job->id}",
            );

            $result = $driver->convert($context);

            $resultFile = $recorder->handle($job->user, $result);

            $job->forceFill([
                'result_file_id' => $resultFile->id,
                'status' => ConversionStatus::Completed,
                'progress' => 100,
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $job->forceFill([
                'status' => ConversionStatus::Failed,
                'error_code' => Str::snake(class_basename($exception)),
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ])->save();

            report($exception);
        }
    }
}
