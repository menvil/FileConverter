<?php

use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Support\Carbon;

it('can create a file record', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->create([
        'user_id' => $user->id,
        'original_name' => 'image.png',
        'stored_path' => 'uploads/test.png',
        'mime_type' => 'image/png',
        'extension' => 'png',
        'size_bytes' => 12345,
        'metadata_json' => ['width' => 100, 'height' => 100],
    ]);

    expect($file->exists)->toBeTrue();
    expect($file->user->is($user))->toBeTrue();
    expect($file->metadata_json)->toBeArray();
    expect($file->metadata_json['width'])->toBe(100);
});

it('casts expires_at to a datetime', function () {
    $file = FileRecord::factory()->create();

    expect($file->expires_at)->toBeInstanceOf(Carbon::class);
});

// CONV-335: Expired file status handling

it('supports expired file status', function () {
    $file = FileRecord::factory()->create([
        'status' => FileStatus::Expired,
    ]);

    expect($file->fresh()->status)->toBe(FileStatus::Expired);
});

it('is expired when status is expired', function () {
    $file = FileRecord::factory()->create([
        'status' => FileStatus::Expired,
        'expires_at' => now()->addDay(),
    ]);

    expect($file->isExpired())->toBeTrue();
});

it('is expired when expires_at is in the past', function () {
    $file = FileRecord::factory()->create([
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->subMinute(),
    ]);

    expect($file->isExpired())->toBeTrue();
});

it('is not expired when expires_at is in the future', function () {
    $file = FileRecord::factory()->create([
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->addDay(),
    ]);

    expect($file->isExpired())->toBeFalse();
});
