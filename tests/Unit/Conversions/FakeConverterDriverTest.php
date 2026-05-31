<?php

declare(strict_types=1);

use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Support\Conversions\DTO\ConversionContext;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\Conversions\FakeConverterDriver;

it('fake converter driver writes fake result file', function () {
    Storage::fake('local');

    $driver = new FakeConverterDriver(key: 'png_to_jpg');
    $context = new ConversionContext(
        job: new ConversionJob,
        sourceFile: new FileRecord,
        options: [],
        outputDirectory: 'conversions/results',
    );

    $result = $driver->convert($context);

    expect($result->extension)->toBe('txt');
    expect($result->sizeBytes)->toBe(11);
    Storage::disk('local')->assertExists($result->path);
});

it('fake converter driver can be configured to fail', function () {
    $driver = new FakeConverterDriver(key: 'png_to_jpg', shouldFail: true);
    $context = new ConversionContext(
        job: new ConversionJob,
        sourceFile: new FileRecord,
        options: [],
        outputDirectory: 'conversions/results',
    );

    expect(fn () => $driver->convert($context))->toThrow(RuntimeException::class);
});
