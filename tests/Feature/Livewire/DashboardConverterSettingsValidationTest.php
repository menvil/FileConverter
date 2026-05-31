<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('shows a validation error for an invalid settings option', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'invalid-quality')
        ->call('validateSettings')
        ->assertHasErrors(['options.quality']);
});

it('passes validation for the default options', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->call('validateSettings')
        ->assertHasNoErrors();
});

it('rejects an unknown settings option', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.bogus', 'value')
        ->call('validateSettings')
        ->assertHasErrors(['options.bogus']);
});

it('moves to the convert step when settings are valid', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert')
        ->assertHasNoErrors();
});

it('stays on the settings step when settings are invalid', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'bad')
        ->call('continueFromSettings')
        ->assertSet('step', 'settings')
        ->assertHasErrors(['options.quality']);
});

it('does not create any extra records when continuing to the convert step', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert');

    expect(FileRecord::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('does not create any extra records during settings validation', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'best')
        ->call('validateSettings')
        ->assertHasNoErrors();

    expect(FileRecord::query()->where('user_id', $user->id)->count())->toBe(1);
});
