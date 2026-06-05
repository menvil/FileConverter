<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased" style="background:var(--ca-bg);color:var(--ca-text);">
    <header
        x-data="{ mobileMenuOpen: false }"
        class="border-b bg-white"
        style="border-color:var(--ca-border);"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-3">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">ConvertAI</a>

            <nav class="hidden gap-6 text-sm text-[var(--ca-muted)] md:flex">
                <a href="{{ url('/dashboard') }}" class="hover:text-[var(--ca-text)]">Dashboard</a>
                <a href="{{ route('history') }}" class="hover:text-[var(--ca-text)]">History</a>
                <a href="{{ route('billing') }}" class="hover:text-[var(--ca-text)]">Billing</a>
            </nav>

            <div class="flex items-center gap-2">
                {{-- Mobile menu button --}}
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
                    aria-controls="mobile-nav"
                    aria-label="Toggle navigation menu"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-[var(--ca-radius-md)] border bg-white ca-focus-ring md:hidden"
                    style="border-color:var(--ca-border);"
                >
                    <svg aria-hidden="true" class="h-5 w-5 text-[var(--ca-text)]" viewBox="0 0 20 20" fill="currentColor">
                        <path x-show="!mobileMenuOpen" fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75Zm0 10.5a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75ZM2 10a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Z" clip-rule="evenodd"/>
                        <path x-show="mobileMenuOpen" d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
                {{-- Language pill --}}
                <button
                    type="button"
                    class="hidden items-center gap-2 rounded-full border bg-white px-3 py-1.5 text-sm font-medium ca-focus-ring sm:inline-flex"
                    style="border-color:var(--ca-border);"
                    aria-label="Language"
                >
                    <svg aria-hidden="true" class="h-4 w-4 text-[var(--ca-muted)]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm5.93 6h-2.55a13.2 13.2 0 0 0-1.2-3.66A6.02 6.02 0 0 1 15.93 8ZM10 4c.69 0 1.86 1.4 2.46 4H7.54C8.14 5.4 9.31 4 10 4ZM4.07 12h2.55c.18 1.31.58 2.55 1.2 3.66A6.02 6.02 0 0 1 4.07 12Zm0-4a6.02 6.02 0 0 1 3.75-3.66A13.2 13.2 0 0 0 6.62 8H4.07Zm5.93 8c-.69 0-1.86-1.4-2.46-4h4.92c-.6 2.6-1.77 4-2.46 4Zm2.18-.34c.62-1.11 1.02-2.35 1.2-3.66h2.55a6.02 6.02 0 0 1-3.75 3.66Z" clip-rule="evenodd"/></svg>
                    <span>EN</span>
                    <svg aria-hidden="true" class="h-3 w-3 text-[var(--ca-muted)]" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06z"/></svg>
                </button>

                {{-- Gift / referral --}}
                <button
                    type="button"
                    class="hidden h-9 w-9 items-center justify-center rounded-full border bg-white text-base ca-focus-ring sm:inline-flex"
                    style="border-color:var(--ca-border);"
                    aria-label="Refer a friend"
                >
                    <span aria-hidden="true">🎁</span>
                </button>

                {{-- Notifications --}}
                <button
                    type="button"
                    class="relative hidden h-9 w-9 items-center justify-center rounded-full border bg-white ca-focus-ring sm:inline-flex"
                    style="border-color:var(--ca-border);"
                    aria-label="Notifications"
                >
                    <svg aria-hidden="true" class="h-4 w-4 text-[var(--ca-text)]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 0 0-6 6v3.2L2.7 13.3a1 1 0 0 0 .7 1.7h13.2a1 1 0 0 0 .7-1.7L16 11.2V8a6 6 0 0 0-6-6Zm-1.5 14a1.5 1.5 0 0 0 3 0h-3Z"/></svg>
                    <span
                        aria-label="3 unread notifications"
                        class="ca-gradient-primary absolute -right-1 -top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-semibold leading-none text-white"
                    >3</span>
                </button>

                <x-user-dropdown />
            </div>
        </div>

        {{-- Mobile nav --}}
        <div
            x-show="mobileMenuOpen"
            x-cloak
            id="mobile-nav"
            class="border-t md:hidden"
            style="border-color:var(--ca-border);"
        >
            <nav class="flex flex-col gap-1 px-4 py-3 text-sm">
                <a href="{{ url('/dashboard') }}" class="rounded-[var(--ca-radius-md)] px-3 py-2 font-medium text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)] ca-focus-ring">Dashboard</a>
                <a href="{{ route('history') }}" class="rounded-[var(--ca-radius-md)] px-3 py-2 font-medium text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)] ca-focus-ring">History</a>
                <a href="{{ route('billing') }}" class="rounded-[var(--ca-radius-md)] px-3 py-2 font-medium text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)] ca-focus-ring">Billing</a>
                <a href="{{ route('settings') }}" class="rounded-[var(--ca-radius-md)] px-3 py-2 font-medium text-[var(--ca-text)] hover:bg-[var(--ca-surface-muted)] ca-focus-ring">Settings</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10">
        {{ $slot ?? '' }}
        @hasSection('content')
            @yield('content')
        @endif
    </main>

    <footer class="border-t bg-white" style="border-color:var(--ca-border);">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-6 text-sm text-[var(--ca-muted)] md:flex-row">
            <span>&copy; {{ date('Y') }} ConvertAI</span>
            <nav class="flex gap-5">
                <a href="#" class="hover:text-[var(--ca-text)]">Privacy Policy</a>
                <a href="#" class="hover:text-[var(--ca-text)]">Terms of Service</a>
                <a href="#" class="hover:text-[var(--ca-text)]">Security</a>
            </nav>
        </div>
    </footer>

    <x-toast-region />
</body>
</html>
