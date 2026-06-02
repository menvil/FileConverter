<section>
    <h2 class="text-xl font-semibold tracking-tight text-[var(--ca-text)]">Recent Conversions</h2>

    <div class="mt-4">
        @if ($conversions->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-[var(--ca-radius-lg)] border border-dashed border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 px-6 py-12 text-center">
                <p class="text-base font-semibold text-[var(--ca-text)]">No conversions yet</p>
                <p class="mt-1 text-sm text-[var(--ca-muted)]">Upload a file to start converting</p>
            </div>
        @else
            <p>TODO: table rows</p>
        @endif
    </div>
</section>
