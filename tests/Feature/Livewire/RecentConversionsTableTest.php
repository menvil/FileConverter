<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Livewire\RecentConversionsTable;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('renders recent conversions table component', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Recent Conversions');
});

it('renders recent conversions section on dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Recent Conversions');
});

it('shows empty state when user has no conversions', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('No conversions yet')
        ->assertSee('Upload a file to start converting');
});

it('filters conversions by completed status', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create(['status' => ConversionStatus::Completed]);
    ConversionJob::factory()->for($user)->create(['status' => ConversionStatus::Failed]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('statusFilter', 'completed')
        ->assertViewHas('conversions', fn ($c) => $c->count() === 1 && $c->first()->status === ConversionStatus::Completed);
});

it('filters conversions by failed status', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create(['status' => ConversionStatus::Completed]);
    ConversionJob::factory()->for($user)->create(['status' => ConversionStatus::Failed]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('statusFilter', 'failed')
        ->assertViewHas('conversions', fn ($c) => $c->count() === 1 && $c->first()->status === ConversionStatus::Failed);
});

it('shows all statuses when filter is all', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create(['status' => ConversionStatus::Completed]);
    ConversionJob::factory()->for($user)->create(['status' => ConversionStatus::Failed]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('statusFilter', 'all')
        ->assertViewHas('conversions', fn ($c) => $c->count() === 2);
});

it('searches conversions by source file name', function () {
    $user = User::factory()->create();

    $matchFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'marketing-report.png',
    ]);

    $otherFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.jpg',
    ]);

    ConversionJob::factory()->for($user)->for($matchFile, 'sourceFile')->create();
    ConversionJob::factory()->for($user)->for($otherFile, 'sourceFile')->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('search', 'marketing')
        ->assertSee('marketing-report.png')
        ->assertDontSee('product-photo.jpg');
});

it('searches conversions by source format', function () {
    $user = User::factory()->create();

    $sourceA = FileRecord::factory()->for($user)->create(['original_name' => 'report.docx']);
    $sourceB = FileRecord::factory()->for($user)->create(['original_name' => 'image.bmp']);

    ConversionJob::factory()->for($user)->for($sourceA, 'sourceFile')->create([
        'source_format' => 'docx',
        'target_format' => 'jpg',
    ]);
    ConversionJob::factory()->for($user)->for($sourceB, 'sourceFile')->create([
        'source_format' => 'bmp',
        'target_format' => 'webp',
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('search', 'docx')
        ->assertSee('DOCX')
        ->assertDontSee('WEBP');
});

it('searches conversions by target format', function () {
    $user = User::factory()->create();

    $sourceA = FileRecord::factory()->for($user)->create(['original_name' => 'image.bmp']);
    $sourceB = FileRecord::factory()->for($user)->create(['original_name' => 'report.docx']);

    ConversionJob::factory()->for($user)->for($sourceA, 'sourceFile')->create([
        'source_format' => 'bmp',
        'target_format' => 'webp',
    ]);
    ConversionJob::factory()->for($user)->for($sourceB, 'sourceFile')->create([
        'source_format' => 'docx',
        'target_format' => 'tiff',
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('search', 'webp')
        ->assertSee('WEBP')
        ->assertDontSee('TIFF');
});

it('shows all conversions when search is empty', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->count(3)->for($user)->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('search', '')
        ->assertViewHas('conversions', fn ($c) => $c->count() === 3);
});

it('shows download action for completed conversion with result file', function () {
    $user = User::factory()->create();

    $result = FileRecord::factory()->for($user)->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'result_file_id' => $result->id,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Download');
});

it('does not show download action for failed conversion', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Failed,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertDontSee('Download');
});

it('does not show download action for processing conversion', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Processing,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertDontSee('Download');
});

it('renders conversion status badge', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Completed');
});

it('renders correct badge for each status', function (string $status, string $expected) {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create(['status' => $status]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee($expected);
})->with([
    ['queued', 'Queued'],
    ['processing', 'Processing'],
    ['completed', 'Completed'],
    ['failed', 'Failed'],
    ['cancelled', 'Cancelled'],
    ['expired', 'Expired'],
]);

it('renders conversion creation date', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'created_at' => now()->setDate(2026, 1, 15)->setTime(10, 30),
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Jan 15, 2026');
});

it('renders result file size when result file exists', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'size_bytes' => 900_000,
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'size_bytes' => 420_000,
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->for($result, 'resultFile')
        ->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('420 KB');
});

it('renders source file size when no result file', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'size_bytes' => 900_000,
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->create(['result_file_id' => null]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('900 KB');
});

it('renders source and target formats in recent conversions table', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('PNG')
        ->assertSee('JPG');
});

it('renders source file name in recent conversions table', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.png',
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($file, 'sourceFile')
        ->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('product-photo.png');
});
