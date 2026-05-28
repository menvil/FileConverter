@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium text-[var(--ca-primary)] focus:outline-none transition'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[var(--ca-muted)] hover:text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)] focus:outline-none focus:text-[var(--ca-text)] focus:bg-[var(--ca-surface-muted)] transition';
$style = ($active ?? false)
    ? 'border-color:var(--ca-primary);background:color-mix(in srgb, var(--ca-primary) 10%, var(--ca-surface));'
    : '';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($style) style="{{ $style }}" @endif>
    {{ $slot }}
</a>
