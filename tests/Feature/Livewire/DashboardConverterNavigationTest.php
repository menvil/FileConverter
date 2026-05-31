<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('goes back from the format step to the uploaded file summary without removing the file', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'original_name' => 'image.png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('backToUploadSummary')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', $file->id)
        ->assertSee('image.png');

    expect(FileRecord::query()->whereKey($file->id)->exists())->toBeTrue();
});

it('resets the selected target format when going back to the upload summary', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->call('backToUploadSummary')
        ->assertSet('step', 'upload')
        ->assertSet('selectedTargetFormat', null);
});

it('goes back from the settings placeholder to the format step preserving the file', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->call('backToFormatStep')
        ->assertSet('step', 'format')
        ->assertSet('currentFileId', $file->id)
        ->assertSee('Convert PNG to');
});

it('returns to upload when navigating back with a missing file', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', 999999)
        ->set('step', 'format')
        ->call('backToUploadSummary')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', null);
});
