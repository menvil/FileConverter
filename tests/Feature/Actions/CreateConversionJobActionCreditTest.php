<?php

declare(strict_types=1);

use App\Actions\Conversions\CreateConversionJobAction;
use App\Contracts\Billing\CreditLedger;
use App\Exceptions\Billing\InsufficientCreditsException;
use App\Models\ConversionJob;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('does not create conversion job when user has insufficient credits', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->creditAccount->forceFill(['balance' => 1])->save();

    $file = FileRecord::factory()->png()->for($user)->create();

    app(CreateConversionJobAction::class)->handle(
        user: $user,
        sourceFile: $file,
        targetFormat: 'pdf',
        options: [],
    );
})->throws(InsufficientCreditsException::class);

it('does not dispatch queue job when credits are insufficient', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->creditAccount->forceFill(['balance' => 1])->save();

    $file = FileRecord::factory()->png()->for($user)->create();

    try {
        app(CreateConversionJobAction::class)->handle(
            user: $user,
            sourceFile: $file,
            targetFormat: 'pdf',
            options: [],
        );
    } catch (InsufficientCreditsException) {
        // expected
    }

    expect(ConversionJob::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('creates conversion job when user has enough credits', function () {
    Queue::fake();

    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 10, 'test');

    $file = FileRecord::factory()->png()->for($user)->create();

    $job = app(CreateConversionJobAction::class)->handle(
        user: $user,
        sourceFile: $file,
        targetFormat: 'jpg',
        options: [],
    );

    expect($job->exists)->toBeTrue();
    expect(ConversionJob::query()->count())->toBe(1);
    Queue::assertPushed(\App\Jobs\ProcessConversionJob::class);
});
