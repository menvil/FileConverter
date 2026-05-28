@props([
    'name',
    'label' => null,
    'hint' => null,
    'error' => null,
    'type' => 'text',
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
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($disabled) disabled @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->merge([
            'class' => 'rounded-[var(--ca-radius-md)] border bg-white px-3 py-2 text-sm ca-focus-ring disabled:opacity-50',
        ]) }}
        style="border-color:{{ $error ? 'var(--ca-danger)' : 'var(--ca-border)' }};"
    />
    @if ($hint)
        <span class="text-xs text-[var(--ca-muted)]">{{ $hint }}</span>
    @endif
    @if ($error)
        <span class="text-xs" style="color:var(--ca-danger);">{{ $error }}</span>
    @endif
</div>
