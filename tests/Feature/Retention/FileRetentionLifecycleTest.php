<?php

declare(strict_types=1);

use App\Actions\Files\StoreUploadedFileAction;
use App\Enums\ConversionStatus;
use App\Enums\FileStatus;
use App\Enums\Plan;
use App\Jobs\CleanupExpiredFilesJob;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// CONV-346: Cleanup and retention final smoke tests

it('handles full file retention lifecycle', function () {
    Carbon::setTestNow('2026-06-01 10:00:00');

    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]); // 1 day retention

    $upload = UploadedFile::fake()->image('sample.png', 100, 100);

    $source = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($source->expires_at)->not->toBeNull();
    expect($source->expires_at->toDateTimeString())->toBe('2026-06-02 10:00:00');

    Storage::disk('local')->assertExists($source->stored_path);

    Carbon::setTestNow($source->expires_at->copy()->addMinute());

    app(CleanupExpiredFilesJob::class)->handle();

    expect($source->fresh()->status)->toBe(FileStatus::Expired);
    Storage::disk('local')->assertMissing($source->stored_path);
});

it('keeps completed conversion history but blocks download after result expires', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'results/old.jpg',
        'expires_at' => now()->subDay(),
        'status' => FileStatus::Expired,
    ]);

    $job = ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'result_file_id' => $resultFile->id,
    ]);

    // Conversion job history remains
    expect(ConversionJob::find($job->id))->not->toBeNull();
    expect(ConversionJob::find($job->id)->status)->toBe(ConversionStatus::Completed);

    // Download is blocked
    $this->actingAs($user)
        ->get(route('conversions.download', $job))
        ->assertStatus(410);
});

it('cleanup is idempotent', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');
    Storage::disk('local')->put('uploads/expired.png', 'fake-content');

    $file = FileRecord::factory()->create([
        'stored_path' => 'uploads/expired.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->subMinute(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();
    expect($file->fresh()->status)->toBe(FileStatus::Expired);

    // Running again should not throw
    expect(fn () => app(CleanupExpiredFilesJob::class)->handle())->not->toThrow(Throwable::class);
    expect($file->fresh()->status)->toBe(FileStatus::Expired);
});

it('does not delete active files during cleanup', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');
    Storage::disk('local')->put('uploads/active.png', 'fake-content');
    Storage::disk('local')->put('uploads/expired.png', 'fake-content');

    $activeFile = FileRecord::factory()->create([
        'stored_path' => 'uploads/active.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->addDay(),
    ]);

    FileRecord::factory()->create([
        'stored_path' => 'uploads/expired.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->subMinute(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();

    Storage::disk('local')->assertExists('uploads/active.png');
    expect($activeFile->fresh()->status)->toBe(FileStatus::Analyzed);
});
