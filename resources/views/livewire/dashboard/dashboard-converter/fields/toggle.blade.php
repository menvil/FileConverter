<div class="flex items-start justify-between gap-4">
    <div class="flex flex-col gap-1">
        <label for="option-{{ $key }}" class="text-sm font-semibold text-[var(--ca-text)]">
            {{ $field['label'] }}
        </label>
        @if (! empty($field['help']))
            <p class="text-xs text-[var(--ca-muted)]">{{ $field['help'] }}</p>
        @endif
    </div>

    <label class="relative inline-flex shrink-0 cursor-pointer items-center">
        <input
            type="checkbox"
            id="option-{{ $key }}"
            wire:model.live="options.{{ $key }}"
            class="peer sr-only">
        <span class="h-6 w-11 rounded-full bg-[var(--ca-border)] transition peer-checked:bg-[var(--ca-primary)] ca-focus-ring"></span>
        <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </label>
</div>
