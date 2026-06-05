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

        $this->dispatch('settings-saved', section: 'profile');
    }

    public function saveConversionPreferences(): void
    {
        $validated = $this->validate([
            'imageQuality' => ['required', Rule::in(config('converter.allowed_image_quality_values'))],
        ]);

        $user = auth()->user();
        $settings = $user->settings ?? [];

        data_set($settings, 'conversion.image_quality', $validated['imageQuality']);

        $user->forceFill([
            'settings' => $settings,
        ])->save();

        $this->dispatch('settings-saved', section: 'conversion');
    }

    public function render(): View
    {
        return view('livewire.account-settings-form');
    }
}
