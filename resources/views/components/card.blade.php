@props([
    'variant' => 'default',
    'padding' => 'md',
])

@php
    $base = 'rounded-[var(--ca-radius-lg)] border';

    $variants = [
        'default' => 'bg-white',
        'elevated' => 'bg-white shadow-[var(--ca-shadow-card)]',
        'interactive' => 'bg-white shadow-[var(--ca-shadow-card)] cursor-pointer transition hover:-translate-y-0.5 hover:brightness-[1.02]',
        'gradient' => 'ca-gradient-primary text-white',
    ];

    $paddings = [
        'none' => '',
        'sm' => 'p-3',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    $classes = implode(' ', array_filter([
        $base,
        $variants[$variant] ?? $variants['default'],
        $paddings[$padding] ?? $paddings['md'],
    ]));
@endphp

<div
    style="border-color:var(--ca-border);"
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</div>
