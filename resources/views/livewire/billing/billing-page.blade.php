<div>
    {{-- Page header --}}
    <section>
        <h1 class="text-3xl font-semibold tracking-tight text-[var(--ca-text)]">Billing</h1>
        <p class="mt-1 text-[var(--ca-muted)]">Manage your plan, credits, and payment details.</p>
    </section>

    {{-- Checkout result notices --}}
    @if($checkoutStatus === 'success')
        <div class="mt-4 rounded-[var(--ca-radius-md)] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <strong>Payment received.</strong> Your plan or credits will update after payment confirmation.
        </div>
    @elseif($checkoutStatus === 'cancelled')
        <div class="mt-4 rounded-[var(--ca-radius-md)] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Checkout was cancelled.</strong> No changes were made.
        </div>
    @endif

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

        {{-- Credits balance card --}}
        <x-card variant="elevated">
            <p class="text-xs font-medium uppercase tracking-wide text-[var(--ca-muted)]">Credits balance</p>
            <div class="mt-2">
                <span class="text-2xl font-semibold text-[var(--ca-text)]">
                    {{ number_format($this->creditsBalance) }}
                </span>
                <span class="ml-1 text-sm text-[var(--ca-muted)]">credits</span>
            </div>
        </x-card>

        {{-- Plan limits card --}}
        <x-card variant="elevated">
            <p class="text-xs font-medium uppercase tracking-wide text-[var(--ca-muted)]">Plan limits</p>
            @php $limits = $this->planLimits; $featureAccess = app(\App\Services\FeatureAccess\FeatureAccessService::class); $user = $this->authUser; @endphp
            <ul class="mt-3 space-y-2 text-sm">
                <li class="flex justify-between">
                    <span class="text-[var(--ca-muted)]">Max file size</span>
                    <span class="font-medium">{{ $limits->maxFileSizeMb }} MB</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-[var(--ca-muted)]">Storage</span>
                    <span class="font-medium">{{ number_format($limits->storageMb) }} MB</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-[var(--ca-muted)]">Retention</span>
                    <span class="font-medium">{{ $limits->retentionDays }} {{ Str::plural('day', $limits->retentionDays) }}</span>
                </li>
                <li class="flex justify-between">
                    <span class="text-[var(--ca-muted)]">API access</span>
                    @if($featureAccess->allows($user, 'api_access'))
                        <x-badge variant="success">Enabled</x-badge>
                    @else
                        <x-badge variant="neutral">Disabled</x-badge>
                    @endif
                </li>
                <li class="flex justify-between">
                    <span class="text-[var(--ca-muted)]">Batch conversion</span>
                    @if($featureAccess->allows($user, 'batch_conversion'))
                        <x-badge variant="success">Enabled</x-badge>
                    @else
                        <x-badge variant="neutral">Disabled</x-badge>
                    @endif
                </li>
            </ul>
        </x-card>

    </div>

    {{-- Available plans section --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold text-[var(--ca-text)]">Available plans</h2>
        <p class="mt-1 text-sm text-[var(--ca-muted)]">Choose the plan that fits your needs.</p>

        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->plans as $plan)
                <x-card variant="elevated" class="{{ $this->authUser->plan->value === $plan->key ? 'ring-2' : '' }}" style="{{ $this->authUser->plan->value === $plan->key ? 'ring-color:var(--ca-primary);' : '' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-[var(--ca-text)]">{{ $plan->label }}</p>
                            <p class="mt-1 text-sm text-[var(--ca-muted)]">{{ number_format($plan->monthlyCredits) }} credits / month</p>
                        </div>
                        @if($this->authUser->plan->value === $plan->key)
                            <x-badge variant="success">Current plan</x-badge>
                        @endif
                    </div>
                    <div class="mt-4">
                        @if($this->authUser->plan->value === $plan->key)
                            <x-button variant="secondary" disabled class="w-full">Current plan</x-button>
                        @elseif(!$plan->isPaid)
                            <x-button variant="secondary" disabled class="w-full">Free</x-button>
                        @else
                            <x-button
                                variant="gradient"
                                class="w-full"
                                wire:click="startSubscriptionCheckout('{{ $plan->key }}')"
                            >
                                Upgrade to {{ $plan->label }}
                            </x-button>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
    </section>

</div>
