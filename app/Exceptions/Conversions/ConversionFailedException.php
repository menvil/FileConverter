<?php

declare(strict_types=1);

namespace App\Exceptions\Conversions;

use App\Exceptions\DomainException;

final class ConversionFailedException extends DomainException
{
    private string $jobId = '';

    public static function forJob(string $jobId, string $reason): self
    {
        $e = new self("Conversion job [{$jobId}] failed: {$reason}");
        $e->jobId = $jobId;

        return $e;
    }

    public function code(): string
    {
        return 'conversion_failed';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return ['conversion_job_id' => $this->jobId];
    }
}
