@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[var(--ca-text)]']) }}>
    {{ $value ?? $slot }}
</label>
