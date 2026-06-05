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

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="mt-4 rounded-[var(--ca-radius-md)] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
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
            @php $limits = $this->planLimits; @endphp
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
                    @if($this->apiAccess)
                        <x-badge variant="success">Enabled</x-badge>
                    @else
                        <x-badge variant="neutral">Disabled</x-badge>
                    @endif
                </li>
                <li class="flex justify-between">
                    <span class="text-[var(--ca-muted)]">Batch conversion</span>
                    @if($this->batchConversion)
                        <x-badge variant="success">Enabled</x-badge>
                    @else
                        <x-badge variant="neutral">Disabled</x-badge>
                    @endif
                </li>
            </ul>
        </x-card>

    </div>

    {{-- Credit packs section --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold text-[var(--ca-text)]">Buy credits</h2>
        <p class="mt-1 text-sm text-[var(--ca-muted)]">Top up your credits balance with a one-time purchase.</p>

        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->creditPacks as $pack)
                <x-card variant="elevated">
                    <p class="font-semibold text-[var(--ca-text)]">{{ $pack->label }}</p>
                    <p class="mt-1 text-sm text-[var(--ca-muted)]">{{ $pack->description }}</p>
                    <div class="mt-4">
                        <x-button
                            variant="secondary"
                            class="w-full"
                            wire:click="buyCreditPack('{{ $pack->key }}')"
                        >
                            Buy {{ $pack->label }}
                        </x-button>
                    </div>
                </x-card>
            @endforeach
        </div>
    </section>

    {{-- Available plans section --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold text-[var(--ca-text)]">Available plans</h2>
        <p class="mt-1 text-sm text-[var(--ca-muted)]">Choose the plan that fits your needs.</p>

        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($this->plans as $plan)
                @php $isCurrent = $this->authUser->plan->value === $plan->key; @endphp
                <x-card variant="elevated" class="{{ $isCurrent ? 'ring-2 ring-[var(--ca-primary)]' : '' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-[var(--ca-text)]">{{ $plan->label }}</p>
                            <p class="mt-1 text-sm text-[var(--ca-muted)]">{{ number_format($plan->monthlyCredits) }} credits / month</p>
                        </div>
                        @if($isCurrent)
                            <x-badge variant="success">Current plan</x-badge>
                        @endif
                    </div>
                    <div class="mt-4">
                        @if($isCurrent)
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

    {{-- Credit history section --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold text-[var(--ca-text)]">Credit history</h2>
        <p class="mt-1 text-sm text-[var(--ca-muted)]">Your credit grants and conversion charges.</p>

        @php
            $transactions = $this->transactions;
            $typeLabels = [
                'registration_grant'          => 'Registration grant',
                'subscription_monthly_grant'  => 'Monthly subscription credits',
                'credit_pack_purchase'        => 'Credit pack purchase',
                'conversion_completed'        => 'Conversion completed',
                'conversion_refund'           => 'Conversion refund',
            ];
        @endphp

        <div class="mt-4 overflow-x-auto">
            @if($transactions->isEmpty())
                <x-card variant="elevated">
                    <div class="py-8 text-center">
                        <p class="font-medium text-[var(--ca-text)]">No credit transactions yet</p>
                        <p class="mt-1 text-sm text-[var(--ca-muted)]">Your credit grants, purchases and conversion charges will appear here.</p>
                        <div class="mt-4 flex justify-center gap-3">
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center gap-2 rounded-[var(--ca-radius-md)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:brightness-110"
                                style="background:var(--ca-primary);"
                            >Start converting</a>
                        </div>
                    </div>
                </x-card>
            @else
                <x-card variant="elevated" padding="none" class="overflow-x-auto">
                    <table class="w-full min-w-[36rem] text-sm">
                        <thead>
                            <tr class="border-b text-left text-xs font-medium uppercase tracking-wide text-[var(--ca-muted)]" style="border-color:var(--ca-border);">
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Reason</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">Balance after</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="divide-color:var(--ca-border);">
                            @foreach($transactions as $tx)
                                @php
                                    $isPositive = $tx->amount > 0;
                                    $typeVariant = match($tx->type->value) {
                                        'grant', 'purchase', 'refund' => 'success',
                                        'spend', 'expiration'         => 'warning',
                                        default                       => 'neutral',
                                    };
                                    $label = $typeLabels[$tx->reason] ?? $tx->reason;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-[var(--ca-muted)]">{{ $tx->created_at->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge variant="{{ $typeVariant }}">{{ ucfirst($tx->type->value) }}</x-badge>
                                    </td>
                                    <td class="px-4 py-3 text-[var(--ca-text)]">{{ $label }}</td>
                                    <td class="px-4 py-3 text-right font-medium {{ $isPositive ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $isPositive ? '+' : '' }}{{ $tx->amount }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-[var(--ca-muted)]">{{ number_format($tx->balance_after) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($transactions->hasPages())
                        <div class="px-4 py-3 border-t" style="border-color:var(--ca-border);">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                </x-card>
            @endif
        </div>
    </section>

    {{-- Manage billing section --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold text-[var(--ca-text)]">Manage billing</h2>
        <p class="mt-1 text-sm text-[var(--ca-muted)]">Payment method, invoices, and subscription details.</p>

        <x-card variant="elevated" class="mt-4">
            <p class="text-sm text-[var(--ca-muted)]">
                Payment method, invoices, and subscription details are handled securely by our payment provider.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-button variant="secondary" wire:click="openCustomerPortal">
                    Invoices &amp; payment portal
                </x-button>
            </div>
            @error('portal')
                <p class="mt-2 text-xs text-amber-700">{{ $message }}</p>
            @enderror
        </x-card>
    </section>

</div>
