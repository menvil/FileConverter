@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full px-3 py-2.5 text-sm rounded-[var(--ca-radius-md)] border bg-[var(--ca-surface)] text-[var(--ca-text)] placeholder:text-[var(--ca-muted)] shadow-sm focus:outline-none focus:border-[var(--ca-primary)] focus:ring-2 focus:ring-[var(--ca-primary)]/20 disabled:opacity-50']) }} style="border-color:var(--ca-border);">
