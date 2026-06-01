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
use Tests\Support\ImageFixture;

it('processes png to jpg conversion job with real driver', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $sourcePath = ImageFixture::png('source.png', width: 300, height: 200);
    $sourceFile = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
        'stored_path' => $sourcePath,
    ]);

    $job = ConversionJob::factory()->for($user)->for($sourceFile, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
        'converter_key' => 'png_to_jpg',
        'options_json' => [
            'quality' => 'high',
            'background' => '#ffffff',
            'resize' => 'original',
            'remove_metadata' => true,
        ],
        'status' => ConversionStatus::Queued,
    ]);

    (new ProcessConversionJob($job->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    $fresh = $job->fresh();

    expect($fresh->status)->toBe(ConversionStatus::Completed);
    expect($fresh->result_file_id)->not->toBeNull();
    expect(Storage::disk('local')->exists($fresh->resultFile->stored_path))->toBeTrue();
});

it('processes jpg to webp conversion job with real driver', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $sourcePath = ImageFixture::jpg('source.jpg', width: 300, height: 200);
    $sourceFile = FileRecord::factory()->for($user)->create([
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'stored_path' => $sourcePath,
    ]);

    $job = ConversionJob::factory()->for($user)->for($sourceFile, 'sourceFile')->create([
        'source_format' => 'jpg',
        'target_format' => 'webp',
        'converter_key' => 'jpg_to_webp',
        'options_json' => [
            'quality' => 'high',
            'resize' => 'original',
            'remove_metadata' => true,
        ],
        'status' => ConversionStatus::Queued,
    ]);

    (new ProcessConversionJob($job->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    $fresh = $job->fresh();

    expect($fresh->status)->toBe(ConversionStatus::Completed);
    expect($fresh->result_file_id)->not->toBeNull();
    expect(Storage::disk('local')->exists($fresh->resultFile->stored_path))->toBeTrue();
});

it('processes png to pdf conversion job with real driver', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $sourcePath = ImageFixture::png('source.png', width: 300, height: 200);
    $sourceFile = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
        'stored_path' => $sourcePath,
    ]);

    $job = ConversionJob::factory()->for($user)->for($sourceFile, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'pdf',
        'converter_key' => 'png_to_pdf',
        'options_json' => [
            'page_size' => 'a4',
            'orientation' => 'auto',
            'margin' => 'small',
            'fit_mode' => 'contain',
        ],
        'status' => ConversionStatus::Queued,
    ]);

    (new ProcessConversionJob($job->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    $fresh = $job->fresh();

    expect($fresh->status)->toBe(ConversionStatus::Completed);
    expect($fresh->result_file_id)->not->toBeNull();
    expect(Storage::disk('local')->exists($fresh->resultFile->stored_path))->toBeTrue();

    $pdfContent = Storage::disk('local')->get($fresh->resultFile->stored_path);
    expect(str_starts_with($pdfContent, '%PDF'))->toBeTrue();
});
