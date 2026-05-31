<?php

declare(strict_types=1);

namespace App\Actions\Conversions;

use App\Enums\ConversionStatus;
use App\Enums\FileFormat;
use App\Jobs\ProcessConversionJob;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\ConverterRegistry;
use App\Support\Converters\Exceptions\UnsupportedFormatException;

final class CreateConversionJobAction
{
    public function __construct(
        private readonly ConverterRegistry $converterRegistry,
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

        $job = ConversionJob::create([
            'user_id' => $user->id,
            'source_file_id' => $sourceFile->id,
            'source_format' => $sourceFormat,
            'target_format' => $normalizedTarget,
            'converter_key' => $converter->key(),
            'options_json' => $normalizedOptions,
            'status' => ConversionStatus::Queued,
            'progress' => 0,
        ]);

        ProcessConversionJob::dispatch($job->id);

        return $job;
    }
}
