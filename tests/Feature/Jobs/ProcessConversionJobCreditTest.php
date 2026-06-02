<?php

declare(strict_types=1);

use App\Actions\Conversions\RecordConversionResultFileAction;
use App\Contracts\Billing\CreditLedger;
use App\Enums\ConversionCreditChargeStatus;
use App\Jobs\ProcessConversionJob;
use App\Models\ConversionCreditCharge;
use App\Models\ConversionJob;
use App\Models\User;
use App\Support\Conversions\ConverterDriverRegistry;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\Conversions\FakeConverterDriver;

it('captures credits after successful conversion', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $user->creditAccount->forceFill(['balance' => 10])->save();

    $job = ConversionJob::factory()
        ->pngToJpg()
        ->queued()
        ->for($user)
        ->create();

    ConversionCreditCharge::factory()
        ->for($user)
        ->for($job, 'conversionJob')
        ->create([
            'estimated_amount' => 1,
            'captured_amount' => 0,
            'status' => ConversionCreditChargeStatus::Estimated,
        ]);

    app()->instance(
        ConverterDriverRegistry::class,
        new ConverterDriverRegistry([new FakeConverterDriver('png_to_jpg')]),
    );

    (new ProcessConversionJob($job->id))->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    expect(app(CreditLedger::class)->balance($user))->toBe(9);
    expect($job->creditCharge->fresh()->status)->toBe(ConversionCreditChargeStatus::Captured);
    expect($job->creditCharge->fresh()->captured_amount)->toBe(1);
});
