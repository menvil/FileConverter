@php($current = data_get($options, $key))
<fieldset class="flex flex-col gap-2">
    <legend class="text-sm font-semibold text-[var(--ca-text)]">{{ $field['label'] }}</legend>

    @if (! empty($field['help']))
        <p class="text-xs text-[var(--ca-muted)]">{{ $field['help'] }}</p>
    @endif

    <div class="inline-flex flex-wrap gap-1 rounded-[var(--ca-radius-md)] border border-[var(--ca-border)] bg-[var(--ca-surface-muted)]/40 p-1">
        @foreach (($field['options'] ?? []) as $option)
            @php($value = $option['value'])
            @php($active = (string) $current === (string) $value)
            <button
                type="button"
                wire:click="$set('options.{{ $key }}', @js($value))"
                aria-pressed="{{ $active ? 'true' : 'false' }}"
                @class([
                    'cursor-pointer rounded-[calc(var(--ca-radius-md)-2px)] px-3 py-1.5 text-sm font-medium transition ca-focus-ring',
                    'bg-white text-[var(--ca-text)] shadow-sm' => $active,
                    'text-[var(--ca-muted)] hover:text-[var(--ca-text)]' => ! $active,
                ])>
                {{ $option['label'] }}
            </button>
        @endforeach
    </div>
</fieldset>
