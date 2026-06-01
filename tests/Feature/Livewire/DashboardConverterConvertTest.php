<?php

declare(strict_types=1);

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\ConversionJob;
use App\Models\FileRecord;
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

it('renders failed state with readable message and no raw error', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->failed()->create([
        'error_code' => 'driver_failed',
        'error_message' => 'Imagick internal raw error',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'failed')
        ->set('currentConversionJobId', $job->id)
        ->assertSee('Conversion failed')
        ->assertSee('Try another file')
        ->assertDontSee('Imagick internal raw error');
});

it('renders converting state while conversion is processing', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->processing()->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'converting')
        ->set('currentConversionJobId', $job->id)
        ->assertSee('Converting')
        ->assertSee('PNG')
        ->assertSee('JPG');
});

it('renders completed state with download action', function () {
    $user = User::factory()->create();
    $resultFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'avatar.jpg',
        'extension' => 'jpg',
    ]);
    $job = ConversionJob::factory()->for($user)->completed()->create([
        'target_format' => 'jpg',
        'result_file_id' => $resultFile->id,
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'completed')
        ->set('currentConversionJobId', $job->id)
        ->assertSee('Done')
        ->assertSee('avatar.jpg')
        ->assertSee('Download');
});

it('moves to completed step when current job is completed', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'converting')
        ->set('currentConversionJobId', $job->id)
        ->call('refreshConversionStatus')
        ->assertSet('step', 'completed');
});

it('moves to failed step when current job is failed', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->failed()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'converting')
        ->set('currentConversionJobId', $job->id)
        ->call('refreshConversionStatus')
        ->assertSet('step', 'failed');
});

it('does nothing on refreshConversionStatus when no job exists', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'converting')
        ->call('refreshConversionStatus')
        ->assertSet('step', 'converting');
});

it('resets dashboard state when user chooses convert another file', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'completed')
        ->set('currentConversionJobId', $job->id)
        ->set('selectedTargetFormat', 'jpg')
        ->set('options', ['quality' => 'high'])
        ->call('convertAnother')
        ->assertSet('step', 'upload')
        ->assertSet('currentConversionJobId', null)
        ->assertSet('selectedTargetFormat', null)
        ->assertSet('options', []);
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
