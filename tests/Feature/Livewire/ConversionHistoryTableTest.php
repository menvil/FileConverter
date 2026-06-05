<?php

declare(strict_types=1);

use App\Livewire\ConversionHistoryTable;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('renders conversion history table component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertOk();
});

it('shows only current user conversion jobs in history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'own-image.png',
    ]);

    $otherFile = FileRecord::factory()->for($otherUser)->create([
        'original_name' => 'other-image.png',
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($ownFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
        ]);

    ConversionJob::factory()
        ->for($otherUser)
        ->for($otherFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
        ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('own-image.png')
        ->assertDontSee('other-image.png');
});
