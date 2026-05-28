@props([
    'name' => 'file.bin',
    'format' => 'file',
    'from' => null,
    'to' => null,
    'percent' => 0,
])

@php
    $percent = max(0, min(100, (int) $percent));
@endphp

<div
    class="rounded-[var(--ca-radius-lg)] border bg-white px-5 py-4 shadow-[var(--ca-shadow-card)]"
    style="border-color:var(--ca-border);"
>
    <div class="flex items-center gap-3">
        <x-file-icon :format="$format" size="md" />
        <div class="flex min-w-0 flex-1 flex-col">
            <span class="truncate text-base font-semibold text-[var(--ca-text)]">{{ $name }}</span>
            @if ($from && $to)
                <span class="text-sm text-[var(--ca-muted)]">
                    <span>{{ strtoupper($from) }}</span>
                    <span aria-hidden="true">→</span>
                    <span class="font-semibold text-[var(--ca-text)]">{{ strtoupper($to) }}</span>
                </span>
            @endif
        </div>
        <span class="text-lg font-bold text-[var(--ca-text)]">{{ $percent }}%</span>
    </div>
    <div
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ $percent }}"
        class="mt-3 h-1.5 w-full overflow-hidden rounded-full"
        style="background:var(--ca-surface-muted);"
    >
        <div class="ca-gradient-primary h-full rounded-full transition-[width]" style="width:{{ $percent }}%;"></div>
    </div>
</div>
