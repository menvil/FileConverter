@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
])

<div class="flex flex-col gap-2">
    @if ($label)
        <span class="text-sm font-medium text-[var(--ca-text)]">{{ $label }}</span>
    @endif
    <div role="radiogroup" class="inline-flex w-full items-center gap-1 rounded-[var(--ca-radius-lg)] border bg-white p-1" style="border-color:var(--ca-border);">
        @foreach ($options as $optValue => $optLabel)
            @php
                $isSelected = (string) $value === (string) $optValue;
                $id = 'cseg_'.$name.'_'.$optValue;
            @endphp
            <label
                for="{{ $id }}"
                @class([
                    'flex-1 cursor-pointer rounded-[var(--ca-radius-md)] px-4 py-2.5 text-center text-sm font-semibold transition',
                    'ca-gradient-primary text-white shadow-sm' => $isSelected,
                    'text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)]' => ! $isSelected,
                ])
            >
                <input
                    id="{{ $id }}"
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $optValue }}"
                    class="sr-only"
                    @checked($isSelected)
                />
                {{ $optLabel }}
            </label>
        @endforeach
    </div>
</div>
