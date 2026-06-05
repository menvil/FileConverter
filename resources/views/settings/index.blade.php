<x-layouts.app title="Settings — ConvertAI">
    <section class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight text-[var(--ca-text)]">Settings</h1>
        <p class="text-[var(--ca-muted)]">Manage your account and conversion preferences.</p>
    </section>

    <div class="mt-8">
        <livewire:account-settings-form />
    </div>
</x-layouts.app>
