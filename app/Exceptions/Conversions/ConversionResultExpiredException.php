<?php

declare(strict_types=1);

namespace App\Exceptions\Conversions;

use App\Exceptions\DomainException;

final class ConversionResultExpiredException extends DomainException
{
    private string $jobId = '';

    public static function forConversion(string $jobId): self
    {
        $e = new self("Conversion result for job [{$jobId}] has expired. Upload the file again to convert.");
        $e->jobId = $jobId;

        return $e;
    }

    public function code(): string
    {
        return 'result_expired';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return ['conversion_job_id' => $this->jobId];
    }
}
