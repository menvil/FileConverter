<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 border rounded-[var(--ca-radius-md)] font-semibold text-sm text-[var(--ca-text)] tracking-wide hover:bg-[var(--ca-surface-muted)] disabled:opacity-50 transition ca-focus-ring']) }} style="background:var(--ca-surface);border-color:var(--ca-border);">
    {{ $slot }}
</button>
