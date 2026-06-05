<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Settings\UpdateConversionPreferencesAction;
use App\Actions\Settings\UpdateProfileNameAction;
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

    /** @return array<string, string> */
    public function imageQualityOptions(): array
    {
        return [
            'medium' => 'Medium',
            'high' => 'High',
            'best' => 'Best',
        ];
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', 'not_regex:/^\s*$/'],
        ]);

        $user = auth()->user();

        app(UpdateProfileNameAction::class)->handle($user, $validated['name']);

        $this->name = $user->fresh()->name;
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

        app(UpdateConversionPreferencesAction::class)->handle(
            $user,
            $validated['imageQuality'],
            (bool) $validated['removeMetadata'],
        );

        $this->preferencesSavedMessage = 'Conversion preferences saved';
        $this->profileSavedMessage = null;

        $this->dispatch('settings-saved', section: 'conversion');
    }

    public function render(): View
    {
        return view('livewire.account-settings-form');
    }
}
