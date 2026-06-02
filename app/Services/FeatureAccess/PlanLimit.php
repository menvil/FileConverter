<?php

declare(strict_types=1);

namespace App\Services\FeatureAccess;

final readonly class PlanLimit
{
    public function __construct(
        public int $maxFileSizeMb,
        public int $storageMb,
        public int $retentionDays,
        public int $monthlyCredits,
    ) {}

    /** @param array<string, int> $limits */
    public static function fromArray(array $limits): self
    {
        return new self(
            maxFileSizeMb: (int) $limits['max_file_size_mb'],
            storageMb: (int) $limits['storage_mb'],
            retentionDays: (int) $limits['retention_days'],
            monthlyCredits: (int) $limits['monthly_credits'],
        );
    }
}
