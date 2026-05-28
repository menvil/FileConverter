@props([
    'name',
    'label' => null,
    'hint' => null,
    'error' => null,
    'disabled' => false,
    'id' => null,
])

@php
    $id = $id ?? 'f_'.$name;
@endphp

<div class="flex flex-col gap-1">
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-medium text-[var(--ca-text)]">{{ $label }}</label>
    @endif
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($disabled) disabled @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->merge([
            'class' => 'rounded-[var(--ca-radius-md)] border bg-white px-3 py-2 text-sm ca-focus-ring disabled:opacity-50',
        ]) }}
        style="border-color:{{ $error ? 'var(--ca-danger)' : 'var(--ca-border)' }};"
    >
        {{ $slot }}
    </select>
    @if ($hint)
        <span class="text-xs text-[var(--ca-muted)]">{{ $hint }}</span>
    @endif
    @if ($error)
        <span class="text-xs" style="color:var(--ca-danger);">{{ $error }}</span>
    @endif
</div>
