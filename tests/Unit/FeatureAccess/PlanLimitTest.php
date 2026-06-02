<?php

// CONV-187: Add PlanLimit DTO

use App\Services\FeatureAccess\PlanLimit;

it('creates plan limit dto from array', function () {
    $dto = PlanLimit::fromArray([
        'max_file_size_mb' => 25,
        'storage_mb' => 250,
        'retention_days' => 1,
        'monthly_credits' => 50,
    ]);

    expect($dto->maxFileSizeMb)->toBe(25);
    expect($dto->storageMb)->toBe(250);
    expect($dto->retentionDays)->toBe(1);
    expect($dto->monthlyCredits)->toBe(50);
});

it('plan limit dto is readonly', function () {
    $dto = PlanLimit::fromArray([
        'max_file_size_mb' => 25,
        'storage_mb' => 250,
        'retention_days' => 1,
        'monthly_credits' => 50,
    ]);

    expect(fn () => $dto->maxFileSizeMb = 999)->toThrow(Error::class);
});
