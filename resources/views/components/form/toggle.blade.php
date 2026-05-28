@props([
    'name',
    'label' => null,
    'checked' => false,
    'disabled' => false,
    'id' => null,
])

@php
    $id = $id ?? 'f_'.$name;
@endphp

<label
    for="{{ $id }}"
    class="inline-flex cursor-pointer items-center gap-3 text-sm font-medium text-[var(--ca-text)]"
    x-data="{ on: {{ $checked ? 'true' : 'false' }} }"
>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="checkbox"
        role="switch"
        class="sr-only"
        @if ($disabled) disabled @endif
        :checked="on"
        @change="on = $event.target.checked"
        {{ $attributes }}
    />
    <span
        class="relative h-6 w-10 rounded-full transition"
        :style="on ? 'background:var(--ca-primary);' : 'background:var(--ca-border);'"
    >
        <span
            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition"
            :style="on ? 'transform:translateX(16px);' : 'transform:translateX(0);'"
        ></span>
    </span>
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</label>
