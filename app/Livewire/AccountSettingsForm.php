<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class AccountSettingsForm extends Component
{
    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
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

    public function render(): View
    {
        return view('livewire.account-settings-form');
    }
}
