<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('preserves settings when navigating back and forward to the same target format', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'best')
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('options.quality', 'best');
});

it('resets incompatible settings when the target format changes', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'best')
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'pdf')
        ->assertSet('selectedTargetFormat', 'pdf')
        ->assertSet('options.page_size', 'auto')
        ->assertSet('options.quality', null)
        ->assertDontSee('Background color');
});

it('keeps the uploaded file when navigating from settings to format', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->call('goToFormatStep')
        ->assertSet('step', 'format')
        ->assertSet('currentFileId', $file->id);
});
