<div class="flex flex-col gap-8">
    <x-card>
        <h2 class="text-xl font-semibold text-[var(--ca-text)] mb-6">Account Settings</h2>

        <form wire:submit="saveProfile" class="flex flex-col gap-4">
            <div>
                <x-input-label for="settings-name" value="Name" />
                <x-text-input
                    id="settings-name"
                    type="text"
                    wire:model="name"
                    class="mt-1 block w-full"
                    autocomplete="name"
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="settings-email" value="Email" />
                <x-text-input
                    id="settings-email"
                    type="email"
                    value="{{ $email }}"
                    class="mt-1 block w-full bg-[var(--ca-surface-muted)] cursor-not-allowed"
                    readonly
                    disabled
                />
                <p class="mt-1 text-sm text-[var(--ca-muted)]">Email changes are not available in this version.</p>
            </div>

            <div class="flex justify-end">
                <x-button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="saveProfile"
                >
                    Save Profile
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card>
        <h2 class="text-xl font-semibold text-[var(--ca-text)] mb-6">Conversion Preferences</h2>

        <form wire:submit="saveConversionPreferences" class="flex flex-col gap-4">
            <div>
                <x-input-label for="settings-image-quality" value="Default Image Quality" />
                <select
                    id="settings-image-quality"
                    wire:model="imageQuality"
                    class="mt-1 block w-full rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-[var(--ca-surface)] px-3 py-2 text-sm text-[var(--ca-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ca-focus-ring)]"
                >
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="best">Best</option>
                </select>
                <x-input-error :messages="$errors->get('imageQuality')" class="mt-2" />
            </div>

            <div class="flex justify-end">
                <x-button
                    type="submit"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="saveConversionPreferences"
                >
                    Save Preferences
                </x-button>
            </div>
        </form>
    </x-card>
</div>
