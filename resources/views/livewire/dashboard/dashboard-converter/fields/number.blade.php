<div class="flex flex-col gap-2">
    <label for="option-{{ $key }}" class="text-sm font-semibold text-[var(--ca-text)]">
        {{ $field['label'] }}
    </label>

    @if (! empty($field['help']))
        <p class="text-xs text-[var(--ca-muted)]">{{ $field['help'] }}</p>
    @endif

    <input
        type="number"
        id="option-{{ $key }}"
        wire:model.live.debounce.300ms="options.{{ $key }}"
        @isset($field['min']) min="{{ $field['min'] }}" @endisset
        @isset($field['max']) max="{{ $field['max'] }}" @endisset
        step="{{ $field['step'] ?? 1 }}"
        class="w-full rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] ca-focus-ring">
</div>
