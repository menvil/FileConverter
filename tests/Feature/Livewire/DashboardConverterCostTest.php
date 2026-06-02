<?php

declare(strict_types=1);

use App\Livewire\Dashboard\DashboardConverter;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('shows estimated conversion cost in settings step', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->png()->for($user)->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'pdf')
        ->assertSee('2 credits');
});

it('shows 1 credit cost for image to image conversion', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->png()->for($user)->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSee('1 credit');
});

it('shows insufficient credits message and does not create job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->creditAccount->forceFill(['balance' => 1])->save();

    $file = FileRecord::factory()->png()->for($user)->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'pdf')
        ->call('continueFromSettings')
        ->call('convert')
        ->assertSee('Not enough credits');

    expect(ConversionJob::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});
