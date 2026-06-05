<?php

declare(strict_types=1);

use App\Livewire\ConversionHistoryTable;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('filters history by search query on file name', function () {
    $user = User::factory()->create();

    $matchingFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'marketing-banner.png',
    ]);

    $otherFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'invoice.pdf',
    ]);

    ConversionJob::factory()->for($user)->for($matchingFile, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    ConversionJob::factory()->for($user)->for($otherFile, 'sourceFile')->create([
        'source_format' => 'pdf',
        'target_format' => 'jpg',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('search', 'marketing')
        ->assertSee('marketing-banner.png')
        ->assertDontSee('invoice.pdf');
});

it('filters history by search query on format', function () {
    $user = User::factory()->create();

    $pngFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'image.png',
    ]);

    $pdfFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'document.pdf',
    ]);

    ConversionJob::factory()->for($user)->for($pngFile, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    ConversionJob::factory()->for($user)->for($pdfFile, 'sourceFile')->create([
        'source_format' => 'pdf',
        'target_format' => 'jpg',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('search', 'png')
        ->assertSee('image.png')
        ->assertDontSee('document.pdf');
});
