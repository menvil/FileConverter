@props([
    'format' => 'unknown',
    'size' => 'md',
])

@php
    $known = [
        'png' => ['label' => 'PNG', 'tone' => 'bg-emerald-100 text-emerald-700'],
        'jpg' => ['label' => 'JPG', 'tone' => 'bg-amber-100 text-amber-700'],
        'jpeg' => ['label' => 'JPG', 'tone' => 'bg-amber-100 text-amber-700'],
        'webp' => ['label' => 'WEBP', 'tone' => 'bg-sky-100 text-sky-700'],
        'pdf' => ['label' => 'PDF', 'tone' => 'bg-red-100 text-red-700'],
    ];

    $key = strtolower((string) $format);
    $resolved = $known[$key] ?? ['label' => 'FILE', 'tone' => 'bg-slate-100 text-slate-600'];

    $sizes = [
        'sm' => 'h-8 w-8 text-[10px]',
        'md' => 'h-10 w-10 text-xs',
        'lg' => 'h-12 w-12 text-sm',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-[var(--ca-radius-md)] font-semibold tracking-wide',
        $sizes[$size] ?? $sizes['md'],
        $resolved['tone'],
    ]);
@endphp

<span aria-hidden="true" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $resolved['label'] }}
</span>
