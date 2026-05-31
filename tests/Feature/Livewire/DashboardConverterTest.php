<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
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
