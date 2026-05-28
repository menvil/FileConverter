@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<section
    class="overflow-hidden rounded-[var(--ca-radius-lg)] border bg-white shadow-[var(--ca-shadow-card)]"
    style="border-color:var(--ca-border);"
>
    <header class="flex items-center justify-between gap-4 border-b px-6 py-4" style="border-color:var(--ca-border);">
        <div>
            <h2 class="text-base font-semibold text-[var(--ca-text)]">{{ $title }}</h2>
            @if ($description)
                <p class="mt-0.5 text-sm text-[var(--ca-muted)]">{{ $description }}</p>
            @endif
        </div>
        @if ($actionLabel)
            <a
                href="{{ $actionUrl ?? '#' }}"
                class="text-sm font-medium text-[var(--ca-primary)] hover:underline ca-focus-ring"
            >
                {{ $actionLabel }}
            </a>
        @endif
    </header>
    <div>
        {{ $slot }}
    </div>
</section>
