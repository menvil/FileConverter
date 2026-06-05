<?php

declare(strict_types=1);

use App\Enums\FileStatus;
use App\Jobs\CleanupExpiredFilesJob;
use App\Models\FileRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

// CONV-337: Test cleanup deletes expired physical files

it('deletes expired physical files from storage', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');
    Storage::disk('local')->put('uploads/expired.png', 'fake-content');

    FileRecord::factory()->create([
        'stored_path' => 'uploads/expired.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->subMinute(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();

    Storage::disk('local')->assertMissing('uploads/expired.png');
});

it('does not delete non expired physical files', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');
    Storage::disk('local')->put('uploads/active.png', 'fake-content');

    FileRecord::factory()->create([
        'stored_path' => 'uploads/active.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->addDay(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();

    Storage::disk('local')->assertExists('uploads/active.png');
});

// CONV-339: Test cleanup marks file records expired

it('marks expired file records as expired after cleanup', function () {
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
});

it('does not mark active file records as expired', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');
    Storage::disk('local')->put('uploads/active.png', 'fake-content');

    $file = FileRecord::factory()->create([
        'stored_path' => 'uploads/active.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->addDay(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();

    expect($file->fresh()->status)->toBe(FileStatus::Analyzed);
});

it('does not crash when physical file is already missing and marks the record expired', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');

    $file = FileRecord::factory()->create([
        'stored_path' => 'uploads/already-gone.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->subMinute(),
    ]);

    expect(fn () => app(CleanupExpiredFilesJob::class)->handle())->not->toThrow(Throwable::class);

    expect($file->fresh()->status)->toBe(FileStatus::Expired);
});
