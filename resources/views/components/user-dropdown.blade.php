@props([
    'name' => 'Account',
    'plan' => 'Free',
    'credits' => 50,
    'initials' => 'AC',
])

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
        class="inline-flex items-center gap-2 rounded-full border bg-white px-2 py-1 text-sm ca-focus-ring"
        style="border-color:var(--ca-border);"
    >
        <span
            aria-hidden="true"
            class="ca-gradient-primary inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
        >{{ $initials }}</span>
        <span class="hidden font-medium text-[var(--ca-text)] md:inline">{{ $name }}</span>
        <svg aria-hidden="true" class="h-4 w-4 text-[var(--ca-muted)]" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06z"/></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        role="menu"
        class="absolute right-0 mt-2 w-64 rounded-[var(--ca-radius-lg)] border bg-white p-3 shadow-[var(--ca-shadow-card)]"
        style="border-color:var(--ca-border);"
    >
        <div class="flex items-center gap-3 border-b pb-3" style="border-color:var(--ca-border);">
            <span
                aria-hidden="true"
                class="ca-gradient-primary inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold"
            >{{ $initials }}</span>
            <div class="flex flex-col">
                <span class="text-sm font-semibold text-[var(--ca-text)]">{{ $name }}</span>
                <span class="text-xs text-[var(--ca-muted)]">{{ $plan }} plan · Credits: {{ $credits }}</span>
            </div>
        </div>
        <nav class="mt-2 flex flex-col text-sm" role="none">
            <a href="#" role="menuitem" class="rounded px-2 py-1.5 hover:bg-[var(--ca-surface-muted)]">Dashboard</a>
            <a href="#" role="menuitem" class="rounded px-2 py-1.5 hover:bg-[var(--ca-surface-muted)]">History</a>
            <a href="#" role="menuitem" class="rounded px-2 py-1.5 hover:bg-[var(--ca-surface-muted)]">Billing</a>
            <a href="#" role="menuitem" class="rounded px-2 py-1.5 hover:bg-[var(--ca-surface-muted)]">Settings</a>
            <div class="my-1 border-t" style="border-color:var(--ca-border);"></div>
            <a href="#" role="menuitem" class="rounded px-2 py-1.5 text-[var(--ca-muted)] hover:bg-[var(--ca-surface-muted)]">Log out</a>
        </nav>
    </div>
</div>
