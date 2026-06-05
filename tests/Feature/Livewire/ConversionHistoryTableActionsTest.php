<?php

declare(strict_types=1);

use App\Livewire\ConversionHistoryTable;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('shows download action for completed non-expired result', function () {
    $user = User::factory()->create();

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'photo.jpg',
        'expires_at' => now()->addDay(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $result->id,
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('Download')
        ->assertSee(route('conversions.download', $job));
});

it('does not show active download for expired result', function () {
    $user = User::factory()->create();

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'photo.jpg',
        'expires_at' => now()->subDay(),
    ]);

    ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $result->id,
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertDontSee('Download');
});

it('does not show download for failed job', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->failed()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertDontSee('Download');
});

it('shows convert again for completed job with available source file', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'expires_at' => now()->addDay(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->for($source, 'sourceFile')->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('Convert Again')
        ->call('convertAgain', $job->id)
        ->assertDispatched('conversion-repeat-requested', conversionJobId: $job->id)
        ->assertRedirect(route('dashboard'));
});

it('does not show convert again when source file is expired', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'expires_at' => now()->subDay(),
    ]);

    ConversionJob::factory()->for($user)->completed()->for($source, 'sourceFile')->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertDontSee('Convert Again');
});
