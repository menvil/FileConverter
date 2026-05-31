<?php

declare(strict_types=1);

use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Support\Conversions\DTO\ConversionContext;

it('creates conversion context dto', function () {
    $job = new ConversionJob;
    $sourceFile = new FileRecord;

    $context = new ConversionContext(
        job: $job,
        sourceFile: $sourceFile,
        options: ['quality' => 'high'],
        outputDirectory: 'conversions/output',
    );

    expect($context->job)->toBe($job);
    expect($context->sourceFile)->toBe($sourceFile);
    expect($context->options)->toBe(['quality' => 'high']);
    expect($context->outputDirectory)->toBe('conversions/output');
});
