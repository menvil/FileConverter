@php
    $user = auth()->user();
@endphp

@if ($user)
    @php
        $displayName = $user->displayName();
        $email = (string) $user->email;
        $initials = $user->initials();
        $planLabel = $user->plan->label();
    @endphp

    <div
        x-data="{ open: false }"
        @keydown.escape.window="open = false"
        @click.outside="open = false"
        class="relative"
    >
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="menu"
            :class="open ? 'border-[var(--ca-primary)] ring-2 ring-[var(--ca-primary)]/20' : ''"
            class="inline-flex items-center gap-2 rounded-full border bg-white py-1 pl-1 pr-3 text-sm ca-focus-ring"
            style="border-color:var(--ca-border);"
        >
            <span
                aria-hidden="true"
                class="ca-gradient-primary inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold"
            >{{ $initials }}</span>
            <span class="hidden flex-col items-start sm:flex">
                <span class="font-semibold leading-tight text-[var(--ca-text)]">{{ $displayName }}</span>
                <span class="text-xs text-[var(--ca-muted)]">{{ $email }}</span>
            </span>
            <svg aria-hidden="true" class="ml-1 h-4 w-4 text-[var(--ca-muted)]" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06z"/></svg>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            role="menu"
            class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-[var(--ca-radius-lg)] border bg-white shadow-[var(--ca-shadow-card)]"
            style="border-color:var(--ca-border);"
        >
            <div class="flex items-start gap-3 p-4">
                <span
                    aria-hidden="true"
                    class="ca-gradient-primary inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-base font-semibold"
                >{{ $initials }}</span>
                <div class="flex min-w-0 flex-1 flex-col">
                    <div class="flex items-center gap-2">
                        <span class="truncate text-base font-semibold text-[var(--ca-text)]">{{ $displayName }}</span>
                        <span class="ca-gradient-primary shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">{{ $planLabel }}</span>
                    </div>
                    <span class="truncate text-sm text-[var(--ca-muted)]">{{ $email }}</span>
                </div>
            </div>

            <nav class="border-t px-2 py-2" style="border-color:var(--ca-border);" role="none">
                <a href="{{ route('dashboard') }}" role="menuitem" class="flex items-center gap-3 rounded-[var(--ca-radius-md)] bg-violet-50 px-3 py-2 text-sm font-medium text-[var(--ca-primary)]">
                    <x-user-dropdown.icon name="home" />
                    <span>Dashboard</span>
                </a>
                <a href="#" role="menuitem" class="flex items-center gap-3 rounded-[var(--ca-radius-md)] px-3 py-2 text-sm font-medium text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)]">
                    <x-user-dropdown.icon name="card" />
                    <span>Billing</span>
                </a>
                <a href="#" role="menuitem" class="flex items-center gap-3 rounded-[var(--ca-radius-md)] px-3 py-2 text-sm font-medium text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)]">
                    <x-user-dropdown.icon name="cog" />
                    <span>Settings</span>
                </a>
            </nav>
        </div>
    </div>
@endif
