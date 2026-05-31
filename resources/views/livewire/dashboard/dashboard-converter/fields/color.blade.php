<div class="flex flex-col gap-2">
    <label for="option-{{ $key }}" class="text-sm font-semibold text-[var(--ca-text)]">
        {{ $field['label'] }}
    </label>

    @if (! empty($field['help']))
        <p class="text-xs text-[var(--ca-muted)]">{{ $field['help'] }}</p>
    @endif

    <div class="flex items-center gap-2">
        <input
            type="color"
            id="option-{{ $key }}"
            wire:model.live="options.{{ $key }}"
            class="h-9 w-12 cursor-pointer rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white p-1 ca-focus-ring">
        <input
            type="text"
            wire:model.live.debounce.300ms="options.{{ $key }}"
            class="w-28 rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] ca-focus-ring"
            aria-label="{{ $field['label'] }} hex value">
    </div>
</div>
