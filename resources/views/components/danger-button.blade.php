<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-[var(--ca-radius-md)] font-semibold text-xs text-white uppercase tracking-widest transition hover:brightness-110 ca-focus-ring']) }} style="background:var(--ca-danger);">
    {{ $slot }}
</button>
