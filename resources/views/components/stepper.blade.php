@props([
    'steps' => [],
    'active' => null,
])

@php
    $steps = array_values($steps);
    $activeIndex = null;
    foreach ($steps as $i => $label) {
        if ((is_int($active) && $i === $active) || (is_string($active) && $label === $active)) {
            $activeIndex = $i;
            break;
        }
    }
@endphp

<ol {{ $attributes->merge(['class' => 'flex w-full flex-wrap items-center gap-3']) }}>
    @foreach ($steps as $i => $label)
        @php
            $isActive = $activeIndex === $i;
            $isDone = $activeIndex !== null && $i < $activeIndex;
        @endphp
        <li class="flex flex-1 items-center gap-3 min-w-[7rem]" @if ($isActive) aria-current="step" @endif>
            <div
                @class([
                    'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition',
                    'ca-gradient-primary text-white shadow-sm' => $isActive,
                    'border bg-white text-[var(--ca-text)]' => $isDone,
                    'border bg-white text-[var(--ca-muted)]' => ! $isActive && ! $isDone,
                ])
                @if ($isDone || (! $isActive && ! $isDone)) style="border-color:var(--ca-border);" @endif
            >
                @if ($isDone)
                    <span aria-hidden="true" class="inline-flex h-5 w-5 items-center justify-center rounded-full" style="background:var(--ca-success);color:white;">
                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7 7a1 1 0 0 1-1.4 0l-3.3-3.3a1 1 0 1 1 1.4-1.4L9 11.6l6.3-6.3a1 1 0 0 1 1.4 0z" clip-rule="evenodd"/></svg>
                    </span>
                @else
                    <span
                        aria-hidden="true"
                        @class([
                            'inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-white/25 text-white' => $isActive,
                            'bg-[var(--ca-surface-muted)] text-[var(--ca-muted)]' => ! $isActive,
                        ])
                    >{{ $i + 1 }}</span>
                @endif
                <span>{{ $label }}</span>
            </div>
            @if (! $loop->last)
                <span class="hidden h-px flex-1 bg-[var(--ca-border)] md:block" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
