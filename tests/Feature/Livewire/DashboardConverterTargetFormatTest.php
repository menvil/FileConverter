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
