<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Support\Conversions\DTO\ConversionContext;
use Illuminate\Support\Str;

final class ConversionContextFactory
{
    /**
     * @param  array<string, mixed>  $options
     */
    public static function forSourcePath(
        string $sourcePath,
        array $options = [],
    ): ConversionContext {
        $sourceFile = new FileRecord;
        $sourceFile->stored_path = $sourcePath;

        $job = new ConversionJob;

        return new ConversionContext(
            job: $job,
            sourceFile: $sourceFile,
            options: $options,
            outputDirectory: 'conversions/results/test-'.Str::random(8),
        );
    }
}
