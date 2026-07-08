@props([
    'format' => 'unknown',
    'size' => 'md',
])

@php
    $known = [
        'png' => ['label' => 'PNG', 'color' => '#10b981'],
        'jpg' => ['label' => 'JPG', 'color' => '#f59e0b'],
        'jpeg' => ['label' => 'JPG', 'color' => '#f59e0b'],
        'webp' => ['label' => 'WEBP', 'color' => '#0ea5e9'],
        'pdf' => ['label' => 'PDF', 'color' => '#ef4444'],
    ];

    $key = strtolower((string) $format);
    $resolved = $known[$key] ?? ['label' => 'FILE', 'color' => '#64748b'];

    $sizes = [
        'sm' => 'h-8 w-[27px]',
        'md' => 'h-10 w-[34px]',
        'lg' => 'h-12 w-10',
    ];

    $fontSize = strlen($resolved['label']) > 3 ? 8.5 : 11;

    $classes = implode(' ', [
        'shrink-0',
        $sizes[$size] ?? $sizes['md'],
    ]);
@endphp

<svg
    aria-hidden="true"
    viewBox="0 0 40 48"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{-- Document sheet with folded corner --}}
    <path d="M0 6a6 6 0 0 1 6-6h19.5L40 14.5V42a6 6 0 0 1-6 6H6a6 6 0 0 1-6-6V6Z" fill="{{ $resolved['color'] }}"/>
    <path d="M25.5 0 40 14.5H29.5a4 4 0 0 1-4-4V0Z" fill="#fff" fill-opacity=".35"/>
    <text x="20" y="36" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif" font-size="{{ $fontSize }}" font-weight="700" letter-spacing=".5" fill="#fff">{{ $resolved['label'] }}</text>
</svg>
