<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Exceptions\Files\InvalidRetentionPolicyException;
use App\Models\User;
use App\Services\FeatureAccess\FeatureAccessService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final class FileRetentionPolicy
{
    public function __construct(
        private readonly FeatureAccessService $features,
    ) {}

    public function expiresAtFor(User $user, ?CarbonInterface $from = null): CarbonInterface
    {
        $days = (int) $this->features->limit($user, 'retention_days');

        if ($days <= 0) {
            throw InvalidRetentionPolicyException::forInvalidDays($days);
        }

        return ($from ?? Date::now())->copy()->addDays($days);
    }
}
