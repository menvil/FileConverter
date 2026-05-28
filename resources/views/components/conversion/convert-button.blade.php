@props([
    'to' => 'png',
    'estSize' => null,
    'showBack' => false,
])

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-4">
        @if ($showBack)
            <button type="button" class="inline-flex items-center gap-2 text-sm font-medium text-[var(--ca-text)] ca-focus-ring">
                <span aria-hidden="true">←</span> Back
            </button>
        @endif
        @if ($estSize)
            <span class="text-sm text-[var(--ca-muted)]">~ est. size: <span class="font-semibold text-[var(--ca-text)]">{{ $estSize }}</span></span>
        @endif
    </div>
    <button
        type="button"
        class="ca-gradient-primary inline-flex items-center justify-center gap-2 rounded-[var(--ca-radius-lg)] px-6 py-3 text-base font-semibold shadow-sm ca-focus-ring"
    >
        <span>Convert Now</span>
        <span aria-hidden="true">→</span>
        <span>{{ strtoupper($to) }}</span>
        <span aria-hidden="true">→</span>
    </button>
</div>
