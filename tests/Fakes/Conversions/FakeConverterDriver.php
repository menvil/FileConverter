<?php

declare(strict_types=1);

namespace Tests\Fakes\Conversions;

use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class FakeConverterDriver implements ConverterDriver
{
    public function __construct(
        private readonly string $key,
        private readonly bool $shouldFail = false,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        if ($this->shouldFail) {
            throw new RuntimeException('Fake conversion failed.');
        }

        $contents = 'fake result';
        $path = $context->outputDirectory.'/fake-result.txt';

        Storage::disk('local')->put($path, $contents);

        return new ConversionResult(
            path: $path,
            originalName: 'fake-result.txt',
            mimeType: 'text/plain',
            extension: 'txt',
            sizeBytes: strlen($contents),
            metadata: [],
        );
    }
}
