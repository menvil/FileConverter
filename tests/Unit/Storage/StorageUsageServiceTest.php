<?php

// CONV-190: Test Storage Usage Calculation / CONV-191: Implement StorageUsageService

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Storage\StorageUsageService;

it('calculates active storage usage for user files', function () {
    $user = User::factory()->create();

    FileRecord::factory()->for($user)->create([
        'size_bytes' => 1000,
        'status' => FileStatus::Analyzed,
    ]);

    FileRecord::factory()->for($user)->create([
        'size_bytes' => 2000,
        'status' => FileStatus::Analyzed,
    ]);

    expect(app(StorageUsageService::class)->usedBytes($user))->toBe(3000);
});

it('excludes expired and deleted files from storage usage', function () {
    $user = User::factory()->create();

    FileRecord::factory()->for($user)->create(['size_bytes' => 1000, 'status' => FileStatus::Analyzed]);
    FileRecord::factory()->for($user)->create(['size_bytes' => 5000, 'status' => FileStatus::Expired]);
    FileRecord::factory()->for($user)->create(['size_bytes' => 7000, 'status' => FileStatus::Deleted]);

    expect(app(StorageUsageService::class)->usedBytes($user))->toBe(1000);
});

it('scopes storage usage to the given user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    FileRecord::factory()->for($user)->create(['size_bytes' => 500, 'status' => FileStatus::Analyzed]);
    FileRecord::factory()->for($other)->create(['size_bytes' => 999999, 'status' => FileStatus::Analyzed]);

    expect(app(StorageUsageService::class)->usedBytes($user))->toBe(500);
});

it('returns zero when user has no files', function () {
    $user = User::factory()->create();

    expect(app(StorageUsageService::class)->usedBytes($user))->toBe(0);
});

it('returns used megabytes as a float', function () {
    $user = User::factory()->create();

    FileRecord::factory()->for($user)->create([
        'size_bytes' => 1024 * 1024,
        'status' => FileStatus::Analyzed,
    ]);

    expect(app(StorageUsageService::class)->usedMegabytes($user))->toBe(1.0);
});
