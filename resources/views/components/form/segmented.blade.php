@props([
    'name',
    'options' => [],
    'value' => null,
    'label' => null,
])

<div class="flex flex-col gap-1">
    @if ($label)
        <span class="text-sm font-medium text-[var(--ca-text)]">{{ $label }}</span>
    @endif
    <div role="radiogroup" class="inline-flex rounded-[var(--ca-radius-md)] border p-1" style="border-color:var(--ca-border);background:var(--ca-surface-muted);">
        @foreach ($options as $optValue => $optLabel)
            @php
                $isSelected = (string) $value === (string) $optValue;
                $id = 'seg_'.$name.'_'.$optValue;
            @endphp
            <label
                for="{{ $id }}"
                @class([
                    'cursor-pointer px-3 py-1.5 text-sm font-medium rounded-[calc(var(--ca-radius-md)-2px)] transition',
                    'bg-white text-[var(--ca-text)] shadow-sm' => $isSelected,
                    'text-[var(--ca-muted)] hover:text-[var(--ca-text)]' => ! $isSelected,
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
