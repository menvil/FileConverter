<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('completes the upload to target format selection flow for a png file', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.png', 600, 400))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->assertSee('Convert PNG to')
        ->assertSee('JPG')
        ->assertSee('WEBP')
        ->assertSee('PDF')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Settings for PNG to JPG');
});

it('does not persist any extra records during target selection', function () {
    // No direct test for ConversionJob — the ConversionJob model/table does not
    // exist before Phase 9. As a proxy, target selection must not create any
    // additional FileRecord rows beyond the single uploaded file.
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('photo.png'))
        ->call('storeUpload')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings');

    expect(FileRecord::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('handles the full back-and-forth navigation flow without losing the file', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('roundtrip.png'))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->call('selectTargetFormat', 'webp')
        ->assertSet('step', 'settings')
        ->call('backToFormatStep')
        ->assertSet('step', 'format')
        ->assertSee('Convert PNG to')
        ->call('backToUploadSummary')
        ->assertSet('step', 'upload')
        ->assertSee('roundtrip.png');

    expect(FileRecord::query()->where('user_id', $user->id)->where('original_name', 'roundtrip.png')->exists())->toBeTrue();
});
