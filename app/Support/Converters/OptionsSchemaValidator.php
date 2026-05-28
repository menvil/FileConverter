<?php

namespace App\Support\Converters;

use App\Support\Converters\Exceptions\InvalidOptionsSchemaException;

final class OptionsSchemaValidator
{
    private const SUPPORTED_TYPES = [
        'select',
        'segmented',
        'toggle',
        'color',
        'number',
        'range',
    ];

    private const TYPES_REQUIRING_OPTIONS = [
        'select',
        'segmented',
    ];

    public function validate(array $schema): void
    {
        $keys = [];

        foreach ($schema as $field) {
            if (! is_array($field)) {
                throw InvalidOptionsSchemaException::becauseFieldIsInvalid();
            }

            $key = $field['key'] ?? null;
            $type = $field['type'] ?? null;
            $label = $field['label'] ?? null;

            if (! is_string($key) || $key === '') {
                throw InvalidOptionsSchemaException::becauseKeyIsMissing();
            }

            if (in_array($key, $keys, true)) {
                throw InvalidOptionsSchemaException::becauseKeyIsDuplicated($key);
            }

            $keys[] = $key;

            if (! is_string($type) || ! in_array($type, self::SUPPORTED_TYPES, true)) {
                throw InvalidOptionsSchemaException::becauseTypeIsUnsupported((string) $type);
            }

            if (! is_string($label) || $label === '') {
                throw InvalidOptionsSchemaException::becauseLabelIsMissing($key);
            }

            if (in_array($type, self::TYPES_REQUIRING_OPTIONS, true)) {
                $this->validateChoiceOptions($key, $field['options'] ?? null);
            }
        }
    }

    private function validateChoiceOptions(string $key, mixed $options): void
    {
        if (! is_array($options) || $options === []) {
            throw InvalidOptionsSchemaException::becauseOptionsAreRequired($key);
        }

        foreach ($options as $option) {
            if (! is_array($option)) {
                throw InvalidOptionsSchemaException::becauseOptionItemIsInvalid($key);
            }

            $value = $option['value'] ?? null;
            $label = $option['label'] ?? null;

            if ($value === null || ! is_string($label) || $label === '') {
                throw InvalidOptionsSchemaException::becauseOptionItemIsInvalid($key);
            }
        }
    }
}
