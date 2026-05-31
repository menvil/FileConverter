<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('does not allow the settings step without an uploaded file', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'settings')
        ->call('ensureValidStep')
        ->assertSet('step', 'upload');
});

it('does not allow the settings step without a selected target format', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->set('selectedTargetFormat', null)
        ->call('goToSettingsStep')
        ->assertSet('step', 'format');
});

it('returns to upload from the settings step when the file is missing', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', 999999)
        ->set('selectedTargetFormat', 'jpg')
        ->call('goToSettingsStep')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', null);
});

it('moves to the settings step when a file and target format are present', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->set('selectedTargetFormat', 'jpg')
        ->call('goToSettingsStep')
        ->assertSet('step', 'settings');
});
