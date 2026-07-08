@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium rounded-[var(--ca-radius-md)] transition focus:outline-none ca-focus-ring cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $variants = [
        'primary' => 'text-white shadow-sm hover:brightness-110',
        'secondary' => 'border border-[var(--ca-border)] bg-white text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)]',
        'ghost' => 'text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)]',
        'gradient' => 'ca-gradient-primary text-white shadow-sm hover:brightness-110',
        'danger' => 'text-white hover:brightness-110',
    ];

    $variantStyle = match ($variant) {
        'primary' => 'background:var(--ca-primary);',
        'danger' => 'background:var(--ca-danger);',
        default => '',
    };

    $classes = implode(' ', array_filter([
        $base,
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ]));
@endphp

<button
    type="{{ $type }}"
    @if ($disabled) disabled @endif
    @if ($variantStyle) style="{{ $variantStyle }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
