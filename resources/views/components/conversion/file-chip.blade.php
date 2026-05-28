@props([
    'name' => 'file.bin',
    'format' => 'file',
    'size' => null,
    'showReplace' => true,
    'showRemove' => true,
])

<div
    class="flex items-center gap-3 rounded-[var(--ca-radius-lg)] border px-4 py-3"
    style="border-color:var(--ca-border);background:color-mix(in srgb, var(--ca-primary) 6%, white);"
>
    <x-file-icon :format="$format" size="md" />
    <div class="flex min-w-0 flex-1 flex-col">
        <span class="truncate text-base font-semibold text-[var(--ca-text)]">{{ $name }}</span>
        @if ($size)
            <span class="text-sm text-[var(--ca-muted)]">{{ strtoupper($format) }} · {{ $size }}</span>
        @else
            <span class="text-sm text-[var(--ca-muted)]">{{ strtoupper($format) }}</span>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if ($showReplace)
            <button type="button" class="rounded-[var(--ca-radius-md)] border bg-white px-3 py-1.5 text-sm font-medium ca-focus-ring" style="border-color:var(--ca-border);">
                Replace
            </button>
        @endif
        @if ($showRemove)
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-[var(--ca-muted)] hover:bg-[var(--ca-surface-muted)] ca-focus-ring" aria-label="Remove file">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        @endif
    </div>
</div>
