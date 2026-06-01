<?php

declare(strict_types=1);

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\ConversionJob;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('creates conversion job when user clicks convert', function () {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.png', 800, 600))
        ->assertSet('step', 'format')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->set('options.quality', 'high')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert')
        ->call('convert')
        ->assertSet('step', 'converting');

    expect(ConversionJob::query()->count())->toBe(1);
});

it('does not create duplicate conversion jobs on repeated convert calls', function () {
    Queue::fake();
    Storage::fake('local');

    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.png', 800, 600))
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'high')
        ->call('continueFromSettings');

    $component->call('convert');
    $component->call('convert');

    expect(ConversionJob::query()->count())->toBe(1);
});
