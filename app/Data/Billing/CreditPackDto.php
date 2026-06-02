<?php

declare(strict_types=1);

namespace App\Data\Billing;

final readonly class CreditPackDto
{
    public function __construct(
        public string $key,
        public string $label,
        public int $credits,
        public ?string $stripePriceId,
        public string $description,
    ) {}

    public static function fromConfig(string $key, array $config): self
    {
        $required = ['label', 'credits', 'stripe_price_id', 'description'];

        foreach ($required as $field) {
            if (! array_key_exists($field, $config)) {
                throw new \InvalidArgumentException("Missing required credit pack config key: {$field}");
            }
        }

        return new self(
            key: $key,
            label: $config['label'],
            credits: $config['credits'],
            stripePriceId: $config['stripe_price_id'],
            description: $config['description'],
        );
    }
}
