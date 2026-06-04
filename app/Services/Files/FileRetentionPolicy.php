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
        $raw = $this->features->limit($user, 'retention_days');

        $validated = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($validated === false) {
            throw InvalidRetentionPolicyException::forInvalidDays($raw);
        }

        return ($from ?? Date::now())->copy()->addDays($validated);
    }
}
