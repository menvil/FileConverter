<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;

final class UpdateConversionPreferencesAction
{
    public function handle(User $user, string $imageQuality, bool $removeMetadata): void
    {
        $settings = $user->settings ?? [];

        data_set($settings, 'conversion.image_quality', $imageQuality);
        data_set($settings, 'conversion.remove_metadata', $removeMetadata);

        $user->forceFill([
            'settings' => $settings,
        ])->save();
    }
}
