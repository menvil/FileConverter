<div class="flex flex-col gap-2">
    <label for="option-{{ $key }}" class="text-sm font-semibold text-[var(--ca-text)]">
        {{ $field['label'] }}
    </label>

    @if (! empty($field['help']))
        <p class="text-xs text-[var(--ca-muted)]">{{ $field['help'] }}</p>
    @endif

    <select
        id="option-{{ $key }}"
        wire:model.live="options.{{ $key }}"
        class="w-full cursor-pointer rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-white px-3 py-2 text-sm text-[var(--ca-text)] ca-focus-ring">
        @if (! empty($field['placeholder']))
            <option value="">{{ $field['placeholder'] }}</option>
        @endif
        @foreach (($field['options'] ?? []) as $option)
            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
        @endforeach
    </select>
</div>
