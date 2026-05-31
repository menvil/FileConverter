<?php

declare(strict_types=1);

use App\Actions\Conversions\CreateConversionJobAction;
use App\Enums\ConversionStatus;
use App\Jobs\ProcessConversionJob;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\Support\Conversions\Exceptions\UnsupportedConversionException;
use App\Support\Converters\Exceptions\InvalidConverterOptionsException;
use Illuminate\Support\Facades\Queue;

it('creates queued conversion job for valid source target and options', function () {
    Queue::fake();

    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    $job = app(CreateConversionJobAction::class)->handle(
        user: $user,
        sourceFile: $file,
        targetFormat: 'jpg',
        options: ['quality' => 'high'],
    );

    expect($job->status)->toBe(ConversionStatus::Queued);
    expect($job->source_format)->toBe('png');
    expect($job->target_format)->toBe('jpg');
    expect($job->converter_key)->toBe('png:jpg');
    expect($job->progress)->toBe(0);
    expect($job->options_json['quality'])->toBe('high');
    expect($job->source_file_id)->toBe($file->id);

    Queue::assertPushed(ProcessConversionJob::class);
});

it('normalizes the source format from the file extension', function () {
    Queue::fake();

    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'jpeg']);

    $job = app(CreateConversionJobAction::class)->handle($user, $file, 'png');

    expect($job->source_format)->toBe('jpg');
    expect($job->converter_key)->toBe('jpg:png');
});

it('rejects unsupported conversion pair', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    app(CreateConversionJobAction::class)->handle($user, $file, 'mp3', []);
})->throws(UnsupportedConversionException::class);

it('rejects invalid converter options before creating job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    try {
        app(CreateConversionJobAction::class)->handle($user, $file, 'jpg', [
            'quality' => 'invalid',
        ]);
        $this->fail('Expected InvalidConverterOptionsException was not thrown.');
    } catch (InvalidConverterOptionsException $exception) {
        expect(ConversionJob::count())->toBe(0);
        Queue::assertNothingPushed();
    }
});
