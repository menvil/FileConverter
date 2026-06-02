<?php

namespace App\Support\Files;

use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final class FileExpirationPolicy
{
    public function __construct(
        private readonly FeatureAccessService $featureAccess,
    ) {}

    public function forUploadedFile(User $user): CarbonInterface
    {
        return $this->calculateExpiration($user);
    }

    public function forResultFile(User $user): CarbonInterface
    {
        return $this->calculateExpiration($user);
    }

    private function calculateExpiration(User $user): CarbonInterface
    {
        $days = $this->featureAccess->limits($user)->retentionDays;

        return Date::now()->addDays($days);
    }
}
