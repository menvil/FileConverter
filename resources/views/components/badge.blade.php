@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $base = 'inline-flex items-center gap-1 rounded-full font-medium';

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];

    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'success' => 'bg-green-100 text-green-700',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-red-100 text-red-700',
        'purple' => 'bg-violet-100 text-violet-700',
        'gradient' => 'ca-gradient-primary',
    ];

    $classes = implode(' ', array_filter([
        $base,
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['neutral'],
    ]));
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
