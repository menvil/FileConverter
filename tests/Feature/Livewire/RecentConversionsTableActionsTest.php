<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Livewire\RecentConversionsTable;
use App\Models\ConversionJob;
use App\Models\User;
use Livewire\Livewire;

it('dispatches convert again event for own conversion', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'target_format' => 'jpg',
        'options_json' => [
            'quality' => 'high',
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('convertAgain', $job->id)
        ->assertDispatched('conversion-repeat-requested');
});

it('does not dispatch convert again event for another users conversion', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $job = ConversionJob::factory()->for($other)->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('convertAgain', $job->id)
        ->assertNotDispatched('conversion-repeat-requested');
});

it('can toggle starred state for own conversion', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->create([
        'is_starred' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('toggleStar', $job->id);

    expect($job->fresh()->is_starred)->toBeTrue();
});

it('cannot toggle starred state for another users conversion', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $job = ConversionJob::factory()->for($other)->create([
        'is_starred' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('toggleStar', $job->id);

    expect($job->fresh()->is_starred)->toBeFalse();
});
