<x-layouts.app title="Checkout cancelled — ConvertAI">
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-[var(--ca-text)]">Billing cancelled</h1>
        <p class="mt-3 text-[var(--ca-muted)]">No changes were made to your subscription.</p>
        <a href="{{ route('dashboard') }}" class="mt-6 text-sm underline text-[var(--ca-text)]">Go to dashboard</a>
    </div>
</x-layouts.app>
