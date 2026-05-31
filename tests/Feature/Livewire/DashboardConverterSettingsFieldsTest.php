<?php

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

/**
 * Helper: put the component on the settings step with a file + target so that
 * the dynamic options form renders for a supplied schema.
 */
function settingsComponent(array $schema, array $options = [])
{
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    return Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->set('selectedTargetFormat', 'jpg')
        ->set('step', 'settings')
        ->set('optionsSchema', $schema)
        ->set('options', $options);
}

it('renders a segmented option field with all choices', function () {
    settingsComponent(
        schema: [
            [
                'key' => 'quality',
                'type' => 'segmented',
                'label' => 'Quality',
                'default' => 'high',
                'options' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                ],
            ],
        ],
        options: ['quality' => 'high'],
    )
        ->assertSee('Quality')
        ->assertSee('Low')
        ->assertSee('Medium')
        ->assertSee('High')
        ->assertSeeHtml('aria-pressed="true"');
});

it('updates the bound option when a segmented choice is clicked', function () {
    settingsComponent(
        schema: [
            [
                'key' => 'quality',
                'type' => 'segmented',
                'label' => 'Quality',
                'default' => 'high',
                'options' => [
                    ['value' => 'medium', 'label' => 'Medium'],
                    ['value' => 'high', 'label' => 'High'],
                ],
            ],
        ],
        options: ['quality' => 'high'],
    )
        ->set('options.quality', 'medium')
        ->assertSet('options.quality', 'medium');
});

it('renders a select option field with all choices', function () {
    settingsComponent(
        schema: [
            [
                'key' => 'page_size',
                'type' => 'select',
                'label' => 'Page size',
                'default' => 'auto',
                'options' => [
                    ['value' => 'auto', 'label' => 'Auto'],
                    ['value' => 'a4', 'label' => 'A4'],
                    ['value' => 'letter', 'label' => 'Letter'],
                ],
            ],
        ],
        options: ['page_size' => 'auto'],
    )
        ->assertSee('Page size')
        ->assertSee('Auto')
        ->assertSee('A4')
        ->assertSee('Letter')
        ->assertSeeHtml('wire:model.live="options.page_size"');
});
