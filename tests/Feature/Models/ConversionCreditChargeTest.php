<?php

declare(strict_types=1);

use App\Enums\ConversionCreditChargeStatus;
use App\Models\ConversionCreditCharge;
use App\Models\ConversionJob;

it('creates conversion credit charge record', function () {
    $charge = ConversionCreditCharge::factory()->create([
        'estimated_amount' => 2,
        'captured_amount' => 0,
        'status' => ConversionCreditChargeStatus::Estimated,
    ]);

    expect($charge->exists)->toBeTrue();
    expect($charge->estimated_amount)->toBe(2);
    expect($charge->captured_amount)->toBe(0);
    expect($charge->status)->toBe(ConversionCreditChargeStatus::Estimated);
});

it('belongs to a user', function () {
    $charge = ConversionCreditCharge::factory()->create();

    expect($charge->user)->not->toBeNull();
});

it('can be linked to a conversion job', function () {
    $charge = ConversionCreditCharge::factory()
        ->for(ConversionJob::factory(), 'conversionJob')
        ->create();

    expect($charge->conversion_job_id)->not->toBeNull();
    expect($charge->conversionJob)->not->toBeNull();
});

it('captured state sets captured amount equal to estimated', function () {
    $charge = ConversionCreditCharge::factory()->create([
        'estimated_amount' => 2,
    ]);

    $charge->forceFill([
        'captured_amount' => $charge->estimated_amount,
        'status' => ConversionCreditChargeStatus::Captured,
    ])->save();

    expect($charge->fresh()->status)->toBe(ConversionCreditChargeStatus::Captured);
    expect($charge->fresh()->captured_amount)->toBe(2);
});
