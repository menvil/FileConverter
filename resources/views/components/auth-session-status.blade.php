@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-[var(--ca-success)]']) }}>
        {{ $status }}
    </div>
@endif
