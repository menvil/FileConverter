<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class AccountSettingsForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $imageQuality = 'high';

    public bool $removeMetadata = true;

    public ?string $profileSavedMessage = null;

    public ?string $preferencesSavedMessage = null;

    public function mount(): void
    {
        $user = auth()->user();
        $settings = $user->settings ?? [];

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->imageQuality = data_get(
            $settings,
            'conversion.image_quality',
            config('converter.user_defaults.image_quality')
        );

        $this->removeMetadata = (bool) data_get(
            $settings,
            'conversion.remove_metadata',
            config('converter.user_defaults.remove_metadata')
        );
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = auth()->user();

        $user->forceFill([
            'name' => trim($validated['name']),
        ])->save();

        $this->name = $user->name;
        $this->profileSavedMessage = 'Profile settings saved';
        $this->preferencesSavedMessage = null;

        $this->dispatch('settings-saved', section: 'profile');
    }

    public function saveConversionPreferences(): void
    {
        $validated = $this->validate([
            'imageQuality' => ['required', Rule::in(config('converter.allowed_image_quality_values'))],
            'removeMetadata' => ['boolean'],
        ]);

        $user = auth()->user();
        $settings = $user->settings ?? [];

        data_set($settings, 'conversion.image_quality', $validated['imageQuality']);
        data_set($settings, 'conversion.remove_metadata', (bool) $validated['removeMetadata']);

        $user->forceFill([
            'settings' => $settings,
        ])->save();

        $this->preferencesSavedMessage = 'Conversion preferences saved';
        $this->profileSavedMessage = null;

        $this->dispatch('settings-saved', section: 'conversion');
    }

    public function render(): View
    {
        return view('livewire.account-settings-form');
    }
}
