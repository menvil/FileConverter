<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Data\Billing\CreditPackDto;
use Illuminate\Support\Collection;

final class CreditPackRepository
{
    /** @return Collection<int, CreditPackDto> */
    public function all(): Collection
    {
        return collect($this->packsConfig())
            ->map(fn (array $config, string $key) => CreditPackDto::fromConfig($key, $config))
            ->values();
    }

    public function find(string $key): ?CreditPackDto
    {
        $config = $this->packsConfig();

        if (! array_key_exists($key, $config)) {
            return null;
        }

        return CreditPackDto::fromConfig($key, $config[$key]);
    }

    public function findOrFail(string $key): CreditPackDto
    {
        $pack = $this->find($key);

        if ($pack === null) {
            throw new \InvalidArgumentException("Unknown credit pack key: {$key}");
        }

        return $pack;
    }

    public function findByStripePriceId(string $stripePriceId): ?CreditPackDto
    {
        return $this->all()->first(fn (CreditPackDto $pack) => $pack->stripePriceId === $stripePriceId);
    }

    private function packsConfig(): array
    {
        return config('billing.credit_packs', []);
    }
}
