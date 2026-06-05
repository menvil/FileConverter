<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use App\ViewModels\TargetFormatCardViewModel;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('renders empty upload state', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Drop your file here')
        ->assertSee('PNG, JPG, WEBP and PDF supported in beta')
        ->assertSee('Choose file');
});

it('does not allow format step without uploaded file', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'format')
        ->call('ensureValidStep')
        ->assertSet('step', 'upload');
});

it('returns to upload step when current file is missing', function () {
    Livewire::test(DashboardConverter::class)
        ->call('goToFormatStep')
        ->assertSet('step', 'upload')
        ->assertSee('Drop your file here');
});

it('handles a stale current file id by returning to upload step', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', 999999)
        ->call('goToFormatStep')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', null);
});

it('opens the format step when a current file exists', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSet('step', 'format');
});

it('loads available target formats for an uploaded png file', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSet('step', 'format')
        ->assertSee('JPG')
        ->assertSee('WEBP')
        ->assertSee('PDF');
});

it('loads available target formats for an uploaded jpg file', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'jpg']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSet('step', 'format')
        ->assertSee('PNG')
        ->assertSee('WEBP')
        ->assertSee('PDF');
});

it('returns an empty target list when there is no current file', function () {
    $component = Livewire::test(DashboardConverter::class);

    expect($component->instance()->availableTargets)->toBe([]);
});

it('renders clickable target format cards with labels and descriptions for png', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSee('Convert PNG to')
        ->assertSee('JPG')
        ->assertSee('Best for photos and sharing')
        ->assertSee('WEBP')
        ->assertSee('PDF')
        ->assertSeeHtml('wire:click="selectTargetFormat(\'jpg\')"');
});

it('exposes target format cards as view models', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $cards = Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->instance()
        ->targetFormatCards;

    expect($cards)->each->toBeInstanceOf(TargetFormatCardViewModel::class);
    expect(collect($cards)->pluck('targetFormat')->all())->toContain('jpg', 'webp', 'pdf');
});

it('renders a recommended badge on the recommended target card', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSee('Recommended');
});

it('shows an empty state when the source format has no available targets', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'pdf']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSet('step', 'format')
        ->assertSee('No conversion targets available')
        ->assertSee('Upload another file')
        ->assertDontSee('Convert PDF to');
});

it('renders upload loading state hooks in the upload dropzone', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSeeHtml('wire:loading')
        ->assertSeeHtml('wire:target="upload,storeUpload"')
        ->assertSee('Uploading');
});

it('renders target selection loading state hooks on format step', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSet('step', 'format')
        ->assertSeeHtml('wire:target="selectTargetFormat"')
        ->assertSee('Loading converter settings');
});

it('does not create duplicate conversion jobs on repeated convert call', function () {
    Queue::fake();

    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->call('convert')
        ->call('convert');

    expect(ConversionJob::query()->where('user_id', $user->id)->count())->toBe(1);
});
