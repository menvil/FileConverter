<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Livewire\ConversionHistoryTable;
use App\Models\ConversionCreditCharge;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

it('renders conversion history table columns', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'original_name' => 'photo.png',
        'size_bytes' => 102400,
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'photo.jpg',
        'size_bytes' => 51200,
    ]);

    ConversionJob::factory()->for($user)->create([
        'source_file_id' => $source->id,
        'result_file_id' => $result->id,
        'source_format' => 'png',
        'target_format' => 'jpg',
        'status' => ConversionStatus::Completed,
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('photo.png')
        ->assertSee('PNG')
        ->assertSee('JPG')
        ->assertSee('Completed');
});

it('renders status badges in history table', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->completed()->create();
    ConversionJob::factory()->for($user)->failed()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('Completed')
        ->assertSee('Failed');
});

it('renders captured credit cost in history table', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->completed()->create();

    ConversionCreditCharge::factory()->for($user)->for($job, 'conversionJob')->create([
        'captured_amount' => 2,
        'status' => 'captured',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('2 credits');
});

it('renders dash when job has no captured credit charge', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->failed()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('—');
});

it('eager-loads sourceFile resultFile and creditCharge without N+1 queries', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $source = FileRecord::factory()->for($user)->create();
        $result = FileRecord::factory()->for($user)->create();
        $job = ConversionJob::factory()->for($user)->completed()->create([
            'source_file_id' => $source->id,
            'result_file_id' => $result->id,
        ]);
        ConversionCreditCharge::factory()->for($user)->for($job, 'conversionJob')->captured()->create();
    }

    DB::enableQueryLog();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class);

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // With eager loading: auth check + jobs + sourceFile + resultFile + creditCharge + pagination count = ~6-7 queries.
    // N+1 would produce 3 queries per row (15+ queries for 5 rows).
    expect($queryCount)->toBeLessThan(10);
});

it('shows helpful empty state on history page when user has no conversions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('No conversion history yet')
        ->assertSee('Start your first conversion');
});

it('shows filter-specific empty state when filters return no results', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('status', 'failed')
        ->assertSee('No conversions match your filters');
});
