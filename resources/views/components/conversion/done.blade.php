@props([
    'name' => 'file.bin',
    'to' => 'pdf',
    'downloadUrl' => '#',
])

<div
    class="rounded-[var(--ca-radius-lg)] border bg-white p-5 shadow-[var(--ca-shadow-card)]"
    style="border-color:var(--ca-border);"
>
    <div class="flex items-center gap-3">
        <span aria-hidden="true" class="inline-flex h-12 w-12 items-center justify-center rounded-[var(--ca-radius-md)]" style="background:var(--ca-success);">
            <svg class="h-6 w-6 text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3.3-3.3a1 1 0 1 1 1.4-1.4L9 11.6l6.3-6.3a1 1 0 0 1 1.4 0z" clip-rule="evenodd"/></svg>
        </span>
        <div class="flex min-w-0 flex-col">
            <span class="text-xl font-bold text-[var(--ca-text)]">Done!</span>
            <span class="flex items-center gap-2 text-sm text-[var(--ca-muted)]">
                <span class="truncate">{{ $name }}</span>
                <span aria-hidden="true">→</span>
                <x-file-icon :format="$to" size="sm" />
                <span class="font-semibold text-[var(--ca-text)]">{{ strtoupper($to) }}</span>
            </span>
        </div>
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-3">
        <a
            href="{{ $downloadUrl }}"
            class="ca-gradient-primary inline-flex items-center justify-center gap-2 rounded-[var(--ca-radius-lg)] px-6 py-2.5 text-sm font-semibold shadow-sm ca-focus-ring"
        >
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3v10m0 0-4-4m4 4 4-4M4 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
            Download file
        </a>
        <button type="button" class="inline-flex items-center justify-center rounded-[var(--ca-radius-lg)] border bg-white px-5 py-2.5 text-sm font-medium ca-focus-ring" style="border-color:var(--ca-border);">
            New conversion
        </button>
    </div>
</div>
