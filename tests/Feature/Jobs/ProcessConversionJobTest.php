<?php

declare(strict_types=1);

use App\Actions\Conversions\RecordConversionResultFileAction;
use App\Enums\ConversionStatus;
use App\Jobs\ProcessConversionJob;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Conversions\ConverterDriverRegistry;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\Conversions\FakeConverterDriver;

it('processes conversion job successfully with fake driver', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $sourceFile = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    $conversionJob = ConversionJob::factory()
        ->for($user)
        ->for($sourceFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'options_json' => ['quality' => 'high'],
            'status' => ConversionStatus::Queued,
            'progress' => 0,
        ]);

    app()->instance(
        ConverterDriverRegistry::class,
        new ConverterDriverRegistry([
            new FakeConverterDriver('png_to_jpg'),
        ]),
    );

    (new ProcessConversionJob($conversionJob->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    $fresh = $conversionJob->fresh();

    expect($fresh->status)->toBe(ConversionStatus::Completed);
    expect($fresh->progress)->toBe(100);
    expect($fresh->result_file_id)->not->toBeNull();
    expect($fresh->started_at)->not->toBeNull();
    expect($fresh->completed_at)->not->toBeNull();

    $resultFile = FileRecord::find($fresh->result_file_id);
    expect($resultFile)->not->toBeNull();
    expect($resultFile->user_id)->toBe($user->id);
});

it('marks conversion job as failed when driver throws exception', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $sourceFile = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $conversionJob = ConversionJob::factory()
        ->for($user)
        ->for($sourceFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'status' => ConversionStatus::Queued,
            'progress' => 0,
        ]);

    app()->instance(
        ConverterDriverRegistry::class,
        new ConverterDriverRegistry([
            new FakeConverterDriver('png_to_jpg', shouldFail: true),
        ]),
    );

    (new ProcessConversionJob($conversionJob->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    $fresh = $conversionJob->fresh();

    expect($fresh->status)->toBe(ConversionStatus::Failed);
    expect($fresh->error_code)->not->toBeNull();
    expect($fresh->error_message)->toContain('Fake conversion failed');
    expect($fresh->result_file_id)->toBeNull();
});

it('ignores conversion jobs that are not queued', function () {
    $user = User::factory()->create();
    $sourceFile = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $conversionJob = ConversionJob::factory()
        ->for($user)
        ->for($sourceFile, 'sourceFile')
        ->create([
            'converter_key' => 'png_to_jpg',
            'status' => ConversionStatus::Completed,
            'progress' => 100,
        ]);

    app()->instance(
        ConverterDriverRegistry::class,
        new ConverterDriverRegistry([
            new FakeConverterDriver('png_to_jpg', shouldFail: true),
        ]),
    );

    (new ProcessConversionJob($conversionJob->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    expect($conversionJob->fresh()->status)->toBe(ConversionStatus::Completed);
});
