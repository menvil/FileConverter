<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Livewire\RecentConversionsTable;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('shows completed conversion in recent conversions after successful conversion flow', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.png',
        'extension' => 'png',
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.jpg',
        'extension' => 'jpg',
        'size_bytes' => 420_000,
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->for($result, 'resultFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'status' => ConversionStatus::Completed,
        ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('product-photo.png')
        ->assertSee('PNG')
        ->assertSee('JPG')
        ->assertSee('Completed')
        ->assertSee('Download');
});

it('shows all table columns populated for a completed conversion', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'original_name' => 'invoice.pdf',
        'extension' => 'pdf',
        'size_bytes' => 2_000_000,
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'invoice.png',
        'extension' => 'png',
        'size_bytes' => 850_000,
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->for($result, 'resultFile')
        ->create([
            'source_format' => 'pdf',
            'target_format' => 'png',
            'status' => ConversionStatus::Completed,
            'created_at' => now()->setDate(2026, 3, 10),
        ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('invoice.pdf')
        ->assertSee('PDF')
        ->assertSee('PNG')
        ->assertSee('850 KB')
        ->assertSee('Mar 10, 2026')
        ->assertSee('Completed')
        ->assertSee('Download');
});

it('does not show empty state when conversions exist', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertDontSee('No conversions yet');
});
