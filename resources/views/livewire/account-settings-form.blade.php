<div class="flex flex-col gap-8">
    <x-card>
        <h2 class="text-xl font-semibold text-[var(--ca-text)] mb-6">Account Settings</h2>

        <div class="flex flex-col gap-4">
            <div>
                <x-input-label for="settings-name" value="Name" />
                <x-text-input
                    id="settings-name"
                    type="text"
                    wire:model="name"
                    class="mt-1 block w-full"
                    autocomplete="name"
                />
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
        </div>
    </x-card>
</div>
