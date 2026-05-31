{{--
    Dynamic options form renderer.

    Renders converter option fields purely from $optionsSchema. It must stay
    converter-agnostic: no source/target conditionals belong here. Each field is
    delegated to a per-type renderer under fields/<type>.blade.php; unknown types
    fall back to a visible controlled message instead of silently disappearing.
--}}
<div class="space-y-5" data-testid="dynamic-options-form">
    @foreach ($optionsSchema as $field)
        @php($fieldKey = $field['key'] ?? null)
        @php($fieldView = 'livewire.dashboard.dashboard-converter.fields.'.($field['type'] ?? 'unknown'))
        <div data-testid="option-field-{{ $fieldKey }}">
            @if ($fieldKey !== null && view()->exists($fieldView))
                @include($fieldView, ['key' => $fieldKey, 'field' => $field])
                @error('options.'.$fieldKey)
                    <p class="mt-1 text-sm text-[var(--ca-danger)]">{{ $message }}</p>
                @enderror
            @else
                <p class="text-sm text-[var(--ca-danger)]">
                    Unsupported field type: {{ $field['type'] ?? 'unknown' }}
                </p>
            @endif
        </div>
    @endforeach
</div>
