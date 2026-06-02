<?php

use App\Actions\Files\StoreUploadedFileAction;
use App\Enums\FileStatus;
use App\Enums\Plan;
use App\Exceptions\Storage\StorageLimitExceededException;
use App\Models\FileRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// CONV-193: Enforce storage limit in action

it('throws storage limit exceeded exception when quota would be exceeded', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]);

    // Fill up to 249 MB (free limit is 250 MB)
    FileRecord::factory()->for($user)->create([
        'size_bytes' => 249 * 1024 * 1024,
        'status' => FileStatus::Analyzed,
    ]);

    $file = UploadedFile::fake()->create('large.png', 2 * 1024, 'image/png');

    expect(fn () => app(StoreUploadedFileAction::class)->handle($user, $file))
        ->toThrow(StorageLimitExceededException::class);
});

// CONV-195: Apply retention days to files

it('sets uploaded file expiration based on free plan retention', function () {
    Storage::fake('local');

    Carbon::setTestNow('2026-01-01 10:00:00');

    $user = User::factory()->create(['plan' => Plan::Free]);
    $file = UploadedFile::fake()->image('image.png');

    $record = app(StoreUploadedFileAction::class)->handle($user, $file);

    expect($record->expires_at->toDateTimeString())->toBe('2026-01-02 10:00:00');
});

it('sets uploaded file expiration based on pro plan retention', function () {
    Storage::fake('local');

    Carbon::setTestNow('2026-01-01 10:00:00');

    $user = User::factory()->create(['plan' => Plan::Pro]);
    $file = UploadedFile::fake()->image('image.png');

    $record = app(StoreUploadedFileAction::class)->handle($user, $file);

    expect($record->expires_at->toDateString())->toBe('2026-01-31');
});

it('stores uploaded png file and creates file record', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->image('avatar.png', 1200, 800);

    $record = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($record)->toBeInstanceOf(FileRecord::class);
    expect($record->user_id)->toBe($user->id);
    expect($record->original_name)->toBe('avatar.png');
    expect($record->extension)->toBe('png');
    expect($record->status)->toBe(FileStatus::Analyzed);
    expect($record->metadata_json['width'])->toBe(1200);
    expect($record->metadata_json['height'])->toBe(800);
    expect($record->checksum)->not->toBeEmpty();
    expect($record->size_bytes)->toBeGreaterThan(0);
    expect($record->expires_at)->not->toBeNull();

    Storage::disk('local')->assertExists($record->stored_path);
});
