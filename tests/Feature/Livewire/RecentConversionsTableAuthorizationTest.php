<?php

declare(strict_types=1);

use App\Livewire\RecentConversionsTable;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Livewire\Livewire;

it('does not render another users conversions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $ownFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'own-file.png',
    ]);

    $otherFile = FileRecord::factory()->for($other)->create([
        'original_name' => 'other-file.png',
    ]);

    ConversionJob::factory()->for($user)->for($ownFile, 'sourceFile')->create();
    ConversionJob::factory()->for($other)->for($otherFile, 'sourceFile')->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('own-file.png')
        ->assertDontSee('other-file.png');
});

it('query is always scoped to the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    ConversionJob::factory()->count(3)->for($user)->create();
    ConversionJob::factory()->count(5)->for($other)->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertViewHas('conversions', fn ($c) => $c->total() === 3);
});

it('download action links to own conversion only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $ownResult = FileRecord::factory()->for($user)->create();
    $otherResult = FileRecord::factory()->for($other)->create();

    $ownJob = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $ownResult->id,
    ]);

    ConversionJob::factory()->for($other)->completed()->create([
        'result_file_id' => $otherResult->id,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSeeHtml(route('conversions.download', $ownJob))
        ->assertDontSeeHtml(route('conversions.download', ConversionJob::where('user_id', $other->id)->first()));
});
