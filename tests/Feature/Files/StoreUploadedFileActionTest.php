<?php

use App\Actions\Files\StoreUploadedFileAction;
use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
