<?php

namespace Tests\Fakes\Converters;

use App\Support\Converters\Contracts\Converter;

final class FakeUnsupportedConverter implements Converter
{
    public function __construct(
        private readonly string $source,
        private readonly string $target,
    ) {}

    public function key(): string
    {
        return "{$this->source}_to_{$this->target}";
    }

    public function sourceFormat(): string
    {
        return $this->source;
    }

    public function targetFormat(): string
    {
        return $this->target;
    }

    public function label(): string
    {
        return strtoupper($this->target);
    }

    public function description(): string
    {
        return 'Unsupported conversion';
    }

    public function optionsSchema(): array
    {
        return [];
    }

    public function validateOptions(array $options): array
    {
        return $options;
    }

    public function isRecommended(): bool
    {
        return false;
    }
}
