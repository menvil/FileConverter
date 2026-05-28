<?php

namespace App\Support\Converters;

use LogicException;

final class OptionsValidator
{
    public function __construct(
        private readonly OptionsSchemaValidator $schemaValidator,
    ) {}

    public function validate(array $schema, array $options): array
    {
        throw new LogicException('Not implemented yet.');
    }
}
