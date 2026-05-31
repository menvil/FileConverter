<div class="flex flex-col gap-2">
    <div class="flex items-center justify-between">
        <label for="option-{{ $key }}" class="text-sm font-semibold text-[var(--ca-text)]">
            {{ $field['label'] }}
        </label>
        <span class="text-sm tabular-nums text-[var(--ca-muted)]" data-testid="range-value-{{ $key }}">
            {{ data_get($options, $key) }}
        </span>
    </div>

    @if (! empty($field['help']))
        <p class="text-xs text-[var(--ca-muted)]">{{ $field['help'] }}</p>
    @endif

    <input
        type="range"
        id="option-{{ $key }}"
        wire:model.live="options.{{ $key }}"
        min="{{ $field['min'] ?? 0 }}"
        max="{{ $field['max'] ?? 100 }}"
        step="{{ $field['step'] ?? 1 }}"
        class="w-full cursor-pointer accent-[var(--ca-primary)] ca-focus-ring">
</div>
