<div>
    {{-- Page header --}}
    <section>
        <h1 class="text-3xl font-semibold tracking-tight text-[var(--ca-text)]">Billing</h1>
        <p class="mt-1 text-[var(--ca-muted)]">Manage your plan, credits, and payment details.</p>
    </section>

    {{-- Summary row --}}
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Current plan card --}}
        <x-card variant="elevated">
            <p class="text-xs font-medium uppercase tracking-wide text-[var(--ca-muted)]">Current plan</p>
            <div class="mt-2 flex items-center gap-2">
                <span class="text-2xl font-semibold text-[var(--ca-text)]">
                    {{ $this->authUser->plan->name }}
                </span>
                <x-badge variant="{{ $this->authUser->plan->value === 'free' ? 'neutral' : 'success' }}">
                    {{ ucfirst($this->authUser->plan->value) }}
                </x-badge>
            </div>
        </x-card>

    </div>
</div>
