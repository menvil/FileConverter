<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('uploads a valid image file and moves to format step', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('sample.png', 600, 400))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->assertSee('sample.png')
        ->assertSee('Choose output format');

    expect(FileRecord::query()->where('original_name', 'sample.png')->exists())->toBeTrue();
});

it('resets current file when replacing uploaded file', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('first.png'))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->call('replaceFile')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', null)
        ->assertSee('Drop your file here');
});

it('removes uploaded file from current flow', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('remove-me.png'))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->call('removeFile')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', null)
        ->assertDontSee('remove-me.png');
});

it('shows an error for unsupported upload format', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->call('storeUpload')
        ->assertSet('step', 'upload')
        ->assertSee('not supported');
});

it('shows format step placeholder after successful upload', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('photo.png'))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->assertSee('Choose output format')
        ->assertSee('Target format selection will be added in Phase 7');
});

it('shows uploaded file summary after upload', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.jpg', 800, 600))
        ->call('storeUpload')
        ->assertSee('avatar.jpg')
        ->assertSee('JPG')
        ->assertSee('KB')
        ->assertSee('Replace')
        ->assertSee('Remove');
});

it('does not create a file record when no file is provided', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->call('storeUpload')
        ->assertSet('step', 'upload')
        ->assertHasErrors(['upload' => 'required']);

    expect(FileRecord::query()->count())->toBe(0);
});
