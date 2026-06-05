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

it('filters history by status', function () {
    $user = User::factory()->create();

    $completedSource = FileRecord::factory()->for($user)->create([
        'original_name' => 'completed-file.png',
    ]);

    $failedSource = FileRecord::factory()->for($user)->create([
        'original_name' => 'failed-file.png',
    ]);

    ConversionJob::factory()->for($user)->completed()->for($completedSource, 'sourceFile')->create();
    ConversionJob::factory()->for($user)->failed()->for($failedSource, 'sourceFile')->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('status', 'completed')
        ->assertSee('completed-file.png')
        ->assertDontSee('failed-file.png');
});

it('filters history by date range', function () {
    $user = User::factory()->create();

    $insideFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'inside-range.png',
    ]);

    $outsideFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'outside-range.png',
    ]);

    ConversionJob::factory()->for($user)->for($insideFile, 'sourceFile')->create([
        'created_at' => now()->subDays(3),
    ]);

    ConversionJob::factory()->for($user)->for($outsideFile, 'sourceFile')->create([
        'created_at' => now()->subDays(30),
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('dateFrom', now()->subDays(7)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee('inside-range.png')
        ->assertDontSee('outside-range.png');
});

it('filters history by source format', function () {
    $user = User::factory()->create();

    $pngSource = FileRecord::factory()->for($user)->create([
        'original_name' => 'png-source-file.png',
    ]);

    $jpgSource = FileRecord::factory()->for($user)->create([
        'original_name' => 'jpg-source-file.jpg',
    ]);

    ConversionJob::factory()->for($user)->for($pngSource, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'webp',
    ]);

    ConversionJob::factory()->for($user)->for($jpgSource, 'sourceFile')->create([
        'source_format' => 'jpg',
        'target_format' => 'webp',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('sourceFormat', 'png')
        ->assertSee('png-source-file.png')
        ->assertDontSee('jpg-source-file.jpg');
});

it('filters history by target format', function () {
    $user = User::factory()->create();

    $jpgTargetSource = FileRecord::factory()->for($user)->create([
        'original_name' => 'to-jpg-file.png',
    ]);

    $webpTargetSource = FileRecord::factory()->for($user)->create([
        'original_name' => 'to-webp-file.png',
    ]);

    ConversionJob::factory()->for($user)->for($jpgTargetSource, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    ConversionJob::factory()->for($user)->for($webpTargetSource, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'webp',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('targetFormat', 'jpg')
        ->assertSee('to-jpg-file.png')
        ->assertDontSee('to-webp-file.png');
});
