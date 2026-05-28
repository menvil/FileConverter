@props([
    'name' => 'target_format',
    'label' => 'Format',
    'value' => 'png',
    'options' => ['png' => 'PNG', 'jpg' => 'JPG', 'webp' => 'WEBP', 'pdf' => 'PDF'],
])

@php
    $currentLabel = $options[$value] ?? strtoupper($value);
@endphp

<div class="flex flex-col gap-2" x-data="{ open: false, value: '{{ $value }}', label: '{{ $currentLabel }}' }">
    @if ($label)
        <span class="text-sm font-medium text-[var(--ca-text)]">{{ $label }}</span>
    @endif
    <div class="relative">
        <button
            type="button"
            @click="open = !open"
            @click.outside="open = false"
            :aria-expanded="open ? 'true' : 'false'"
            class="flex w-full items-center justify-between rounded-[var(--ca-radius-lg)] border bg-white px-3 py-3 text-left ca-focus-ring"
            style="border-color:var(--ca-border);"
        >
            <span class="flex items-center gap-3">
                <template x-for="(opt, key) in {{ json_encode($options) }}" :key="key">
                    <span x-show="key === value" class="contents">
                        <span x-html="`{{ '' }}`"></span>
                    </span>
                </template>
                <x-file-icon :format="$value" size="sm" />
                <span class="text-base font-semibold text-[var(--ca-text)]" x-text="label">{{ $currentLabel }}</span>
            </span>
            <svg aria-hidden="true" class="h-4 w-4 text-[var(--ca-muted)] transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06z"/></svg>
        </button>
        <ul
            x-show="open"
            x-cloak
            x-transition.opacity
            role="listbox"
            class="absolute left-0 right-0 z-30 mt-2 overflow-hidden rounded-[var(--ca-radius-lg)] border bg-white shadow-[var(--ca-shadow-card)]"
            style="border-color:var(--ca-border);"
        >
            @foreach ($options as $optValue => $optLabel)
                <li>
                    <button
                        type="button"
                        @click="value = '{{ $optValue }}'; label = '{{ $optLabel }}'; open = false"
                        class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm font-medium text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)]"
                    >
                        <x-file-icon :format="$optValue" size="sm" />
                        <span>{{ $optLabel }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <input type="hidden" name="{{ $name }}" :value="value" />
    </div>
</div>
