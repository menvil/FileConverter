@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 text-[var(--ca-text)] focus:outline-none transition'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[var(--ca-muted)] hover:text-[var(--ca-text)] focus:outline-none focus:text-[var(--ca-text)] transition';
$styleAttr = ($active ?? false) ? 'border-color:var(--ca-primary);' : '';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($styleAttr) style="{{ $styleAttr }}" @endif>
    {{ $slot }}
</a>
