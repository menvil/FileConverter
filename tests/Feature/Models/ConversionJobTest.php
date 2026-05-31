<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;

it('can create conversion job record', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    $job = ConversionJob::factory()
        ->for($user)
        ->for($file, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'status' => ConversionStatus::Queued,
        ]);

    expect($job->exists)->toBeTrue();
    expect($job->status)->toBe(ConversionStatus::Queued);
    expect($job->options_json)->toBeArray();
});

it('belongs to user and source file', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create();

    $job = ConversionJob::factory()
        ->for($user)
        ->for($file, 'sourceFile')
        ->create();

    expect($job->user->is($user))->toBeTrue();
    expect($job->sourceFile->is($file))->toBeTrue();
    expect($user->conversionJobs->contains($job))->toBeTrue();
    expect($file->sourceConversionJobs->contains($job))->toBeTrue();
});

it('can belong to result file', function () {
    $user = User::factory()->create();
    $source = FileRecord::factory()->for($user)->create();
    $result = FileRecord::factory()->for($user)->create();

    $job = ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->for($result, 'resultFile')
        ->create();

    expect($job->resultFile->is($result))->toBeTrue();
    expect($result->resultConversionJobs->contains($job))->toBeTrue();
});
