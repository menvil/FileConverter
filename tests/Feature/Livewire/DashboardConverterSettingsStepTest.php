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

it('renders the dynamic options form container on the settings step', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->set('selectedTargetFormat', 'jpg')
        ->set('step', 'settings')
        ->set('optionsSchema', [
            ['key' => 'mystery', 'type' => 'definitely_unknown', 'label' => 'Mystery'],
        ])
        ->assertSeeHtml('data-testid="dynamic-options-form"')
        ->assertSeeHtml('data-testid="option-field-mystery"');
});

it('loads the converter options schema when a target format is selected', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->assertSet('selectedConverterKey', 'png:jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Quality')
        ->assertSee('Background color');
});

it('clears the loaded schema when an unsupported target is selected', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedConverterKey', 'png:jpg')
        ->call('selectTargetFormat', 'mp3')
        ->assertSet('selectedConverterKey', null)
        ->assertSet('optionsSchema', [])
        ->assertSet('step', 'format');
});

it('shows a controlled fallback for an unsupported field type', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->set('selectedTargetFormat', 'jpg')
        ->set('step', 'settings')
        ->set('optionsSchema', [
            ['key' => 'mystery', 'type' => 'definitely_unknown', 'label' => 'Mystery'],
        ])
        ->assertSee('Unsupported field type: definitely_unknown');
});
