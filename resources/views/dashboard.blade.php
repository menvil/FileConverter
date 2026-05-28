<x-layouts.app title="Dashboard — ConvertAI">
    <section class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight text-[var(--ca-text)]">Convert any file</h1>
        <p class="text-[var(--ca-muted)]">Upload, choose a target format, fine-tune the result, and convert in one click.</p>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 flex flex-col gap-6">
            <x-card variant="elevated">
                <x-stepper :steps="['File', 'Format', 'Settings', 'Convert']" active="File" />

                <div
                    class="mt-6 flex flex-col items-center gap-3 rounded-[var(--ca-radius-lg)] border-2 border-dashed px-6 py-12 text-center"
                    style="border-color:var(--ca-border);background:var(--ca-surface-muted);"
                >
                    <div aria-hidden="true" class="ca-gradient-primary inline-flex h-12 w-12 items-center justify-center rounded-full text-xl font-semibold">+</div>
                    <h2 class="text-base font-semibold text-[var(--ca-text)]">Drop your files here</h2>
                    <p class="text-sm text-[var(--ca-muted)]">or click to browse · PNG, JPG, WEBP, PDF</p>
                    <x-button variant="gradient" size="md">Select file</x-button>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2 text-sm text-[var(--ca-muted)]">
                    <span>Supported formats:</span>
                    <x-file-icon format="png" size="sm" />
                    <x-file-icon format="jpg" size="sm" />
                    <x-file-icon format="webp" size="sm" />
                    <x-file-icon format="pdf" size="sm" />
                </div>
            </x-card>

            <x-card variant="default">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-[var(--ca-text)]">Settings preview</h2>
                    <x-badge variant="purple" size="sm">Coming soon</x-badge>
                </div>
                <p class="mt-1 text-sm text-[var(--ca-muted)]">Conversion options will appear here once a file is selected.</p>
            </x-card>
        </div>

        <aside class="flex flex-col gap-6">
            <x-card variant="gradient">
                <h2 class="text-base font-semibold">Your plan</h2>
                <p class="mt-1 text-sm opacity-90">Free · 50 credits remaining</p>
                <p class="mt-3 text-xs opacity-80">Upgrade for batch conversions and larger files.</p>
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
        <x-table.shell title="Recent Conversions" description="Your conversions will appear here" actionLabel="View all" actionUrl="#">
            <x-table.empty-state
                title="No conversions yet"
                message="Upload a file above and your conversion history will appear here."
            />
        </x-table.shell>
    </div>

    <div class="mt-10">
        <x-footer-help-cards />
    </div>
</x-layouts.app>
