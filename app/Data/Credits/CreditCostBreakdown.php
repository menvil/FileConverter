<?php

declare(strict_types=1);

namespace App\Data\Credits;

final readonly class CreditCostBreakdown
{
    public function __construct(
        public int $base,
        public int $size,
        public int $features,
        public int $total,
        public array $details,
    ) {
        if ($this->base < 0) {
            throw new \InvalidArgumentException('Breakdown base cannot be negative.');
        }
        if ($this->size < 0) {
            throw new \InvalidArgumentException('Breakdown size cannot be negative.');
        }
        if ($this->features < 0) {
            throw new \InvalidArgumentException('Breakdown features cannot be negative.');
        }
        if ($this->total < 0) {
            throw new \InvalidArgumentException('Breakdown total cannot be negative.');
        }
        if ($this->total !== ($this->base + $this->size + $this->features)) {
            throw new \InvalidArgumentException(
                "Breakdown total ({$this->total}) must equal base + size + features ({$this->base} + {$this->size} + {$this->features})."
            );
        }
    }

    public function toArray(): array
    {
        return [
            'base' => $this->base,
            'size' => $this->size,
            'features' => $this->features,
            'total' => $this->total,
            'details' => $this->details,
        ];
    }
}
