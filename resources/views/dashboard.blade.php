<x-layouts.app title="Dashboard — ConvertAI">
    <section class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight text-[var(--ca-text)]">Convert any file</h1>
        <p class="text-[var(--ca-muted)]">Upload, choose a target format, fine-tune the result, and convert in one click.</p>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            <livewire:dashboard.dashboard-converter />
        </div>

        <aside class="flex flex-col gap-6">
            <x-card variant="gradient">
                <h2 class="text-base font-semibold">Your plan</h2>
                <p class="mt-1 text-sm opacity-90">PRO · 50 credits remaining</p>
                <p class="mt-3 text-xs opacity-80">Upgrade to Max for batch conversions and larger files.</p>
            </x-card>

            <x-card variant="elevated">
                <h2 class="text-base font-semibold text-[var(--ca-text)]">Popular conversions</h2>
                <ul class="mt-3 flex flex-col gap-2 text-sm">
                    <li class="flex items-center justify-between rounded-[var(--ca-radius-sm)] px-2 py-1 hover:bg-[var(--ca-surface-muted)]">
                        <span>PNG → JPG</span>
                        <span class="text-xs text-[var(--ca-muted)]">Image</span>
                    </li>
                    <li class="flex items-center justify-between rounded-[var(--ca-radius-sm)] px-2 py-1 hover:bg-[var(--ca-surface-muted)]">
                        <span>JPG → WEBP</span>
                        <span class="text-xs text-[var(--ca-muted)]">Image</span>
                    </li>
                    <li class="flex items-center justify-between rounded-[var(--ca-radius-sm)] px-2 py-1 hover:bg-[var(--ca-surface-muted)]">
                        <span>Image → PDF</span>
                        <span class="text-xs text-[var(--ca-muted)]">Document</span>
                    </li>
                </ul>
            </x-card>
        </aside>
    </div>

    <div class="mt-10">
        <x-footer-help-cards />
    </div>
</x-layouts.app>
