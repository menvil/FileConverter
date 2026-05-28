@props([
    'title' => 'Nothing here yet',
    'message' => null,
    'actionLabel' => null,
    'actionUrl' => null,
])

<div class="flex flex-col items-center gap-3 px-6 py-12 text-center">
    <div aria-hidden="true" class="ca-gradient-primary inline-flex h-12 w-12 items-center justify-center rounded-full text-lg font-semibold">+</div>
    <h3 class="text-base font-semibold text-[var(--ca-text)]">{{ $title }}</h3>
    @if ($message)
        <p class="max-w-md text-sm text-[var(--ca-muted)]">{{ $message }}</p>
    @endif
    @if ($actionLabel)
        <a
            href="{{ $actionUrl ?? '#' }}"
            class="mt-2 inline-flex items-center justify-center rounded-[var(--ca-radius-md)] px-4 py-2 text-sm font-medium ca-gradient-primary ca-focus-ring"
        >
            {{ $actionLabel }}
        </a>
    @endif
</div>
