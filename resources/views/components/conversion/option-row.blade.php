@props([
    'name',
    'label',
    'description' => null,
    'ai' => false,
    'checked' => false,
])

<div
    class="flex items-center justify-between rounded-[var(--ca-radius-lg)] border bg-white px-4 py-3"
    style="border-color:var(--ca-border);"
>
    <div class="flex flex-col">
        <span class="flex items-center gap-2 text-base font-semibold text-[var(--ca-text)]">
            {{ $label }}
            @if ($ai)
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" style="background:color-mix(in srgb, var(--ca-primary) 12%, white);color:var(--ca-primary);">AI</span>
            @endif
        </span>
        @if ($description)
            <span class="text-sm text-[var(--ca-muted)]">{{ $description }}</span>
        @endif
    </div>
    <x-form.toggle :name="$name" :checked="$checked" />
</div>
