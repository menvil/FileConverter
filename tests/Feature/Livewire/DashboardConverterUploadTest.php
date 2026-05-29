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

it('shows uploaded file summary after upload', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.jpg', 800, 600))
        ->call('storeUpload')
        ->assertSee('avatar.jpg')
        ->assertSee('JPG')
        ->assertSee('Replace')
        ->assertSee('Remove');
});
