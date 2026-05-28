@props([
    'variant' => 'simple',
    'maxSize' => '50 MB',
    'formats' => 'JPEG, PNG, PDF, and MP4',
])

@if ($variant === 'illustrated')
    <div
        class="flex flex-col items-center gap-4 rounded-[var(--ca-radius-lg)] border-2 border-dashed px-6 py-12 text-center"
        style="border-color:var(--ca-border);background:var(--ca-surface-muted);"
    >
        <span aria-hidden="true" class="relative inline-flex">
            <svg class="h-16 w-20 text-[var(--ca-primary)]" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="6" y="14" width="28" height="36" rx="3" transform="rotate(-8 20 32)"/>
                <rect x="32" y="14" width="28" height="36" rx="3" transform="rotate(6 46 32)"/>
                <rect x="18" y="10" width="28" height="36" rx="3" fill="white"/>
                <circle cx="27" cy="22" r="2" fill="currentColor"/>
                <path d="M22 36l5-6 4 4 5-7 7 9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <h2 class="text-2xl font-semibold tracking-tight text-[var(--ca-text)]">
            Drag &amp; drop
            <span class="text-[var(--ca-primary)]">images</span>,
            <span class="text-[var(--ca-primary)]">videos</span>,
            or any
            <span class="text-[var(--ca-primary)]">file</span>
        </h2>
        <p class="text-sm text-[var(--ca-muted)]">
            or <a href="#" class="font-medium text-[var(--ca-primary)] underline">browse files</a> on your computer
        </p>
    </div>
@else
    <div
        class="flex flex-col items-center gap-3 rounded-[var(--ca-radius-lg)] border-2 border-dashed px-6 py-10 text-center"
        style="border-color:var(--ca-border);background:var(--ca-surface);"
    >
        <span aria-hidden="true" class="inline-flex h-10 w-10 items-center justify-center rounded-full" style="background:var(--ca-surface-muted);">
            <svg class="h-5 w-5 text-[var(--ca-text)]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M5 13a4 4 0 0 1 .5-7.97A5 5 0 0 1 15 6a3.5 3.5 0 0 1 0 7" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 9v6m0-6-2.5 2.5M10 9l2.5 2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <h2 class="text-base font-semibold text-[var(--ca-text)]">Choose a file or drag &amp; drop it here.</h2>
        <p class="text-sm text-[var(--ca-muted)]">{{ $formats }} formats, up to {{ $maxSize }}.</p>
        <x-button variant="secondary" size="md">Browse File</x-button>
    </div>
@endif
