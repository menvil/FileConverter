<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('completes the upload, target and settings flow for png to jpg', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.png', 600, 400))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Quality')
        ->set('options.quality', 'best')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert')
        ->assertHasNoErrors();

    // Phase 8 must not create conversion jobs: as a proxy, only the single
    // uploaded FileRecord should exist (ConversionJob arrives in Phase 9).
    expect(FileRecord::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('renders different settings for png to pdf than png to jpg', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSee('Background color')
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'pdf')
        ->assertHasNoErrors()
        ->assertSee('Page size')
        ->assertDontSee('Background color');
});
