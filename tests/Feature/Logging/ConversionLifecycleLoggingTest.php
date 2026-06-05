<?php

declare(strict_types=1);

use App\Models\ConversionJob;
use App\Models\User;
use App\Support\Logging\ConversionLogger;
use Illuminate\Support\Facades\Log;

it('logs conversion job created event with context', function () {
    Log::spy();

    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->create([
        'converter_key' => 'png_to_jpg',
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    app(ConversionLogger::class)->jobCreated($job);

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) => $message === 'conversion.job.created'
            && isset($context['conversion_job_id'])
            && isset($context['user_id'])
            && isset($context['converter_key'])
        );
});

it('logs conversion job completed event with context', function () {
    Log::spy();

    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->create([
        'converter_key' => 'png_to_jpg',
    ]);

    app(ConversionLogger::class)->jobCompleted($job);

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) => $message === 'conversion.job.completed'
            && isset($context['conversion_job_id'])
            && isset($context['user_id'])
        );
});

it('logs conversion job failed event with error code', function () {
    Log::spy();

    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->create([
        'converter_key' => 'png_to_jpg',
        'error_code' => 'driver_error',
        'error_message' => 'Driver failed.',
    ]);

    app(ConversionLogger::class)->jobFailed($job);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message, array $context) => $message === 'conversion.job.failed'
            && isset($context['conversion_job_id'])
            && isset($context['user_id'])
            && isset($context['error_code'])
        );
});
