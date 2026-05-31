<?php

declare(strict_types=1);

use App\Support\Conversions\Contracts\ConverterDriver;
use App\Support\Conversions\DTO\ConversionContext;
use App\Support\Conversions\DTO\ConversionResult;

it('defines converter driver contract', function () {
    $driver = new class implements ConverterDriver
    {
        public function key(): string
        {
            return 'fake_driver';
        }

        public function convert(ConversionContext $context): ConversionResult
        {
            return new ConversionResult(
                path: 'fake/output.txt',
                originalName: 'output.txt',
                mimeType: 'text/plain',
                extension: 'txt',
                sizeBytes: 10,
                metadata: [],
            );
        }
    };

    expect($driver->key())->toBe('fake_driver');
    expect($driver)->toBeInstanceOf(ConverterDriver::class);
});
