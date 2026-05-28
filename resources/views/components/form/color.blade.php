@props([
    'name',
    'label' => null,
    'value' => '#ffffff',
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
    <div class="inline-flex items-center gap-2 rounded-[var(--ca-radius-md)] border bg-white px-2 py-1" style="border-color:var(--ca-border);">
        <input
            id="{{ $id }}"
            type="color"
            name="{{ $name }}"
            value="{{ $value }}"
            @if ($disabled) disabled @endif
            class="h-7 w-9 cursor-pointer rounded border-0 bg-transparent p-0"
            {{ $attributes }}
        />
        <span class="text-xs font-mono text-[var(--ca-muted)]">{{ $value }}</span>
    </div>
</div>
