<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('selects a supported target format and moves to the settings step', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Settings for PNG to JPG');
});

it('rejects an unsupported target format selection', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'mp3')
        ->assertSet('selectedTargetFormat', null)
        ->assertSet('step', 'format')
        ->assertSee('This conversion is not supported');
});

it('resets the selected target format when the current file is removed', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->call('removeFile')
        ->assertSet('selectedTargetFormat', null)
        ->assertSet('currentFileId', null)
        ->assertSet('step', 'upload');
});

it('resets the selected target format when replacing the current file', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->call('replaceFile')
        ->assertSet('selectedTargetFormat', null)
        ->assertSet('targetFormatError', null)
        ->assertSet('step', 'upload');
});

it('shows the unsupported conversion error message on invalid target selection', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'mp3')
        ->assertSee('This conversion is not supported yet.');
});

it('renders a loading indicator targeting target selection', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSeeHtml('wire:loading')
        ->assertSeeHtml('wire:target="selectTargetFormat"');
});

it('clears a previous target error when a valid target is chosen', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'mp3')
        ->assertSet('targetFormatError', 'This conversion is not supported yet.')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('targetFormatError', null)
        ->assertSet('selectedTargetFormat', 'jpg');
});
