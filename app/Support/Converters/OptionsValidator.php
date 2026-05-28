<?php

namespace App\Support\Converters;

use App\Support\Converters\Exceptions\InvalidConverterOptionsException;

final class OptionsValidator
{
    public function __construct(
        private readonly OptionsSchemaValidator $schemaValidator,
    ) {}

    public function validate(array $schema, array $options): array
    {
        $this->schemaValidator->validate($schema);

        $schemaByKey = [];

        foreach ($schema as $field) {
            $schemaByKey[$field['key']] = $field;
        }

        foreach (array_keys($options) as $key) {
            if (! array_key_exists($key, $schemaByKey)) {
                throw InvalidConverterOptionsException::becauseOptionIsUnknown((string) $key);
            }
        }

        $normalized = [];

        foreach ($schemaByKey as $key => $field) {
            $hasUserValue = array_key_exists($key, $options);
            $hasDefault = array_key_exists('default', $field);

            if (! $hasUserValue && ! $hasDefault) {
                if (($field['required'] ?? false) === true) {
                    throw InvalidConverterOptionsException::becauseOptionIsRequired($key);
                }

                continue;
            }

            $value = $hasUserValue ? $options[$key] : $field['default'];

            $normalized[$key] = $this->normalizeValue($field, $value);
        }

        return $normalized;
    }

    private function normalizeValue(array $field, mixed $value): mixed
    {
        $key = $field['key'];
        $type = $field['type'];

        return match ($type) {
            'select', 'segmented' => $this->ensureAllowed($key, $value, $field['options'] ?? []),
            'toggle' => $this->normalizeToggle($key, $value),
            'number', 'range' => $this->ensureNumeric($key, $value),
            'color' => $this->ensureColor($key, $value),
            default => $value,
        };
    }

    private function ensureAllowed(string $key, mixed $value, array $options): mixed
    {
        foreach ($options as $option) {
            if (($option['value'] ?? null) === $value) {
                return $value;
            }
        }

        throw InvalidConverterOptionsException::becauseValueIsNotAllowed($key);
    }

    private function normalizeToggle(string $key, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }

        if ($value === 0 || $value === '0' || $value === 'false' || $value === '' || $value === null) {
            return false;
        }

        throw InvalidConverterOptionsException::becauseValueIsNotAllowed($key);
    }

    private function ensureNumeric(string $key, mixed $value): int|float
    {
        if (! is_numeric($value)) {
            throw InvalidConverterOptionsException::becauseValueIsNotAllowed($key);
        }

        return $value + 0;
    }

    private function ensureColor(string $key, mixed $value): string
    {
        if (! is_string($value) || ! preg_match('/^#?[0-9a-fA-F]{3,8}$/', $value)) {
            throw InvalidConverterOptionsException::becauseValueIsNotAllowed($key);
        }

        return $value;
    }
}
