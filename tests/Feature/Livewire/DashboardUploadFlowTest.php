<?php

// CONV-189: Enforce Max File Size / CONV-192+193: Storage Limit / CONV-197: Integration

use App\Enums\FileStatus;
use App\Enums\Plan;
use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// Max file size enforcement (CONV-189)

it('rejects upload above plan max file size for free plan', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]);

    // Free plan limit is 25 MB; fake file of 26 MB (26 * 1024 KB)
    $file = UploadedFile::fake()->create('large.png', 26 * 1024, 'image/png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->assertHasErrors(['upload']);
});

it('accepts upload within plan max file size for free plan', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]);

    $file = UploadedFile::fake()->image('small.png', 100, 100);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->assertSet('step', 'format')
        ->assertHasNoErrors(['upload']);
});

it('pro plan accepts valid uploads within pro max file size limit', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Pro]);

    // A standard image — well within pro limit (250 MB); tests that validation doesn't block it
    $file = UploadedFile::fake()->image('photo.png', 800, 600);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->assertSet('step', 'format')
        ->assertHasNoErrors(['upload']);
});

it('upload error message includes plan max size', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]);

    $file = UploadedFile::fake()->create('large.png', 26 * 1024, 'image/png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->assertHasErrors(['upload'])
        ->assertSee('25 MB');
});

// Storage limit enforcement (CONV-192+193)

it('rejects upload when storage quota would be exceeded', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]);

    // Free storage limit is 250 MB; fill up to 249 MB
    FileRecord::factory()->for($user)->create([
        'size_bytes' => 249 * 1024 * 1024,
        'status' => FileStatus::Analyzed,
    ]);

    // 2 MB upload would push past 250 MB limit
    $file = UploadedFile::fake()->create('image.png', 2 * 1024, 'image/png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->assertSet('step', 'upload')
        ->assertSet('uploadError', 'You have reached your storage limit. Delete some files to upload more.');
});

it('allows upload when storage quota is not exceeded', function () {
    Storage::fake('local');

    $user = User::factory()->create(['plan' => Plan::Free]);

    // 10 MB used — well under 250 MB limit
    FileRecord::factory()->for($user)->create([
        'size_bytes' => 10 * 1024 * 1024,
        'status' => FileStatus::Analyzed,
    ]);

    $file = UploadedFile::fake()->image('photo.png', 100, 100);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->assertSet('step', 'format')
        ->assertHasNoErrors(['upload']);
});
