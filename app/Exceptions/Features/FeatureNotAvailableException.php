<?php

declare(strict_types=1);

namespace App\Exceptions\Features;

use App\Exceptions\DomainException;

final class FeatureNotAvailableException extends DomainException
{
    private string $feature = '';

    private string $plan = '';

    public static function forFeature(string $feature, string $plan): self
    {
        $e = new self("Feature [{$feature}] is not available on the [{$plan}] plan.");
        $e->feature = $feature;
        $e->plan = $plan;

        return $e;
    }

    public function code(): string
    {
        return 'feature_not_available';
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return [
            'feature' => $this->feature,
            'plan' => $this->plan,
        ];
    }
}
