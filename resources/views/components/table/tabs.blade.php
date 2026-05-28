@props([
    'tabs' => [],
    'active' => null,
])

<div role="tablist" class="inline-flex items-center gap-1 rounded-full border bg-white p-1" style="border-color:var(--ca-border);">
    @foreach ($tabs as $tab)
        @php
            $label = is_array($tab) ? ($tab['label'] ?? '') : (string) $tab;
            $count = is_array($tab) ? ($tab['count'] ?? null) : null;
            $isActive = $active === $label;
        @endphp
        <button
            type="button"
            role="tab"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
            @class([
                'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition',
                'bg-[var(--ca-surface-muted)] text-[var(--ca-text)]' => $isActive,
                'text-[var(--ca-muted)] hover:text-[var(--ca-text)]' => ! $isActive,
            ])
        >
            <span>{{ $label }}</span>
            @if ($count !== null)
                <span @class([
                    'inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-semibold',
                    'bg-white text-[var(--ca-text)]' => $isActive,
                    'bg-[var(--ca-surface-muted)] text-[var(--ca-muted)]' => ! $isActive,
                ])>{{ $count }}</span>
            @endif
        </button>
    @endforeach
</div>
