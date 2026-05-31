<?php

declare(strict_types=1);

namespace App\Support\Conversions\DTO;

use App\Models\ConversionJob;
use App\Models\FileRecord;

final class ConversionContext
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly ConversionJob $job,
        public readonly FileRecord $sourceFile,
        public readonly array $options,
        public readonly string $outputDirectory,
    ) {}
}
