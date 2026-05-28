<?php

namespace App\Support\Converters\Contracts;

interface Converter
{
    public function key(): string;

    public function sourceFormat(): string;

    public function targetFormat(): string;

    public function label(): string;

    public function description(): string;

    public function optionsSchema(): array;

    public function validateOptions(array $options): array;
}
