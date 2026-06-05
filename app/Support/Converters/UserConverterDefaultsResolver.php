<?php

declare(strict_types=1);

namespace App\Support\Converters;

use App\Models\User;

final class UserConverterDefaultsResolver
{
    /**
     * Apply user conversion preferences to a converter schema, returning default values.
     *
     * Only fields present in the schema are populated; user preferences for absent fields
     * are silently ignored.
     *
     * @param  array<int, array<string, mixed>>  $schema
     * @return array<string, mixed>
     */
    public function apply(User $user, array $schema): array
    {
        $settings = $user->settings ?? [];

        $imageQuality = data_get(
            $settings,
            'conversion.image_quality',
            config('converter.user_defaults.image_quality')
        );

        if (! in_array($imageQuality, config('converter.allowed_image_quality_values'), true)) {
            $imageQuality = config('converter.user_defaults.image_quality');
        }

        $removeMetadata = data_get(
            $settings,
            'conversion.remove_metadata',
            config('converter.user_defaults.remove_metadata')
        );

        $schemaKeys = array_column($schema, 'key');
        $defaults = [];

        foreach ($schema as $field) {
            if (! isset($field['key']) || ! array_key_exists('default', $field)) {
                continue;
            }

            $defaults[$field['key']] = $field['default'];
        }

        if (in_array('quality', $schemaKeys, true)) {
            $defaults['quality'] = $imageQuality;
        }

        if (in_array('remove_metadata', $schemaKeys, true)) {
            $defaults['remove_metadata'] = (bool) $removeMetadata;
        }

        return $defaults;
    }
}
