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

<ol {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }}>
    @foreach ($steps as $i => $label)
        @php
            $isActive = $activeIndex === $i;
            $isDone = $activeIndex !== null && $i < $activeIndex;
        @endphp
        <li class="flex items-center gap-3" @if ($isActive) aria-current="step" @endif>
            <span
                @class([
                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                    'ca-gradient-primary' => $isActive,
                    'bg-[var(--ca-success)] text-white' => $isDone,
                    'bg-[var(--ca-surface-muted)] text-[var(--ca-muted)]' => ! $isActive && ! $isDone,
                ])
            >
                @if ($isDone)
                    &check;
                @else
                    {{ $i + 1 }}
                @endif
            </span>
            <span @class([
                'text-sm font-medium',
                'text-[var(--ca-text)]' => $isActive || $isDone,
                'text-[var(--ca-muted)]' => ! $isActive && ! $isDone,
            ])>{{ $label }}</span>
            @if (! $loop->last)
                <span class="h-px w-6 bg-[var(--ca-border)]" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
