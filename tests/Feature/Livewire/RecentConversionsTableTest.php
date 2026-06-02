<?php

declare(strict_types=1);

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
