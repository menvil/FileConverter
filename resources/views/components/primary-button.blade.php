<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ca-gradient-primary inline-flex items-center justify-center px-5 py-2.5 border border-transparent rounded-[var(--ca-radius-md)] font-semibold text-sm text-[var(--ca-on-primary)] tracking-wide shadow-sm transition hover:brightness-110 ca-focus-ring']) }}>
    {{ $slot }}
</button>
