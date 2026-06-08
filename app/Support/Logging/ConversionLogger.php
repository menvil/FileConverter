<?php

declare(strict_types=1);

namespace App\Support\Logging;

use App\Models\ConversionJob;
use Illuminate\Support\Facades\Log;

final class ConversionLogger
{
    public function jobCreated(ConversionJob $job): void
    {
        Log::info('conversion.job.created', $this->baseContext($job));
    }

    public function jobStarted(ConversionJob $job): void
    {
        Log::info('conversion.job.started', $this->baseContext($job));
    }

    public function jobCompleted(ConversionJob $job): void
    {
        Log::info('conversion.job.completed', $this->baseContext($job));
    }

    public function jobFailed(ConversionJob $job): void
    {
        Log::error('conversion.job.failed', array_merge($this->baseContext($job), [
            'error_code' => $job->error_code,
            'error_message' => $job->error_message,
        ]));
    }

    /** @return array<string, mixed> */
    private function baseContext(ConversionJob $job): array
    {
        return [
            'conversion_job_id' => $job->id,
            'user_id' => $job->user_id,
            'source_file_id' => $job->source_file_id,
            'converter_key' => $job->converter_key,
            'source_format' => $job->source_format,
            'target_format' => $job->target_format,
            'status' => $job->status?->value ?? $job->status,
        ];
    }
}
