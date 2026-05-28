<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased" style="background:var(--ca-bg);color:var(--ca-text);">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">ConvertAI</a>
            <div class="flex items-center gap-6">
                <nav class="hidden gap-6 text-sm text-slate-600 md:flex">
                    <a href="{{ url('/dashboard') }}" class="hover:text-slate-900">Dashboard</a>
                    <a href="#" class="hover:text-slate-900">Tools</a>
                    <a href="#" class="hover:text-slate-900">Pricing</a>
                </nav>
                <x-user-dropdown />
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10">
        {{ $slot ?? '' }}
        @hasSection('content')
            @yield('content')
        @endif
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-6 text-sm text-slate-500 md:flex-row">
            <span>&copy; {{ date('Y') }} ConvertAI</span>
            <nav class="flex gap-5">
                <a href="#" class="hover:text-slate-900">Privacy Policy</a>
                <a href="#" class="hover:text-slate-900">Terms of Service</a>
                <a href="#" class="hover:text-slate-900">Security</a>
            </nav>
        </div>
    </footer>
</body>
</html>
