<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'File Converter') }} — Convert Any Image, Instantly</title>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        .hero-bg {
            background: radial-gradient(ellipse 80% 60% at 60% 40%, rgba(139,92,246,0.12) 0%, transparent 60%),
                        radial-gradient(ellipse 50% 50% at 85% 20%, rgba(251,146,60,0.10) 0%, transparent 50%),
                        radial-gradient(ellipse 60% 40% at 10% 70%, rgba(59,130,246,0.08) 0%, transparent 50%),
                        #ffffff;
        }
        .stats-bg {
            background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 20%, #4f46e5 45%, #0ea5e9 70%, #f97316 100%);
        }
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #6366f1 50%, #3b82f6 100%);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #6d28d9 0%, #4f46e5 50%, #2563eb 100%);
        }
        .format-icon { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .format-icon:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .pill-tag {
            background: linear-gradient(135deg, rgba(139,92,246,0.08), rgba(59,130,246,0.08));
            border: 1px solid rgba(139,92,246,0.2);
        }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased font-sans">

{{-- ═══════════ NAVIGATION ═══════════ --}}
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl btn-primary flex items-center justify-center shadow-sm flex-shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m0-3l-3-3m0 0l-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5a2.25 2.25 0 01-2.25-2.25v-.75"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-900">File<span class="bg-gradient-to-r from-violet-600 to-blue-500 bg-clip-text text-transparent">Converter</span></span>
            </a>

            <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-gray-600">
                <a href="{{ route('dashboard') }}" class="hover:text-gray-900 transition-colors">Convert</a>
                <a href="{{ route('docs.api') }}" class="hover:text-gray-900 transition-colors">API</a>
                <a href="{{ route('billing') }}" class="hover:text-gray-900 transition-colors">Pricing</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors px-4 py-2">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors px-4 py-2">Sign in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-sm font-semibold text-white btn-primary px-5 py-2 rounded-lg shadow-sm hover:shadow-md transition-all">Sign up free</a>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</header>

{{-- ═══════════ HERO ═══════════ --}}
<section class="hero-bg overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <div>
                <div class="inline-flex items-center gap-2 pill-tag rounded-full px-4 py-1.5 text-xs font-semibold text-violet-700 mb-6">
                    <span class="w-1.5 h-1.5 bg-violet-500 rounded-full animate-pulse"></span>
                    PNG · JPG · WEBP · PDF — Now Live
                </div>

                <h1 class="text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.05] mb-6">
                    Any Image.<br>
                    Any Format.<br>
                    <span class="bg-gradient-to-r from-violet-600 via-blue-500 to-cyan-500 bg-clip-text text-transparent">Instantly.</span>
                </h1>

                <p class="text-lg text-gray-500 leading-relaxed mb-8 max-w-md">
                    Convert, compress and transform images in seconds. Fast, secure and incredibly simple — no software to install.
                </p>

                <div class="flex flex-wrap gap-3 mb-8">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 text-base font-semibold text-white btn-primary px-7 py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all">
                            Start Converting
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 text-base font-semibold text-white btn-primary px-7 py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all">
                            Get Started Free
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-base font-semibold text-gray-700 bg-white px-7 py-3.5 rounded-xl border border-gray-200 hover:border-gray-300 hover:shadow-sm transition-all">
                            Sign in
                        </a>
                    @endauth
                </div>

                <div class="flex flex-wrap items-center gap-6 text-sm text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <div class="flex -space-x-1.5">
                            <div class="w-7 h-7 rounded-full border-2 border-white" style="background:#7c3aed"></div>
                            <div class="w-7 h-7 rounded-full border-2 border-white" style="background:#3b82f6"></div>
                            <div class="w-7 h-7 rounded-full border-2 border-white" style="background:#06b6d4"></div>
                            <div class="w-7 h-7 rounded-full border-2 border-white" style="background:#10b981"></div>
                        </div>
                        <span class="font-medium text-gray-600">1k+ users</span>
                    </div>
                    <div class="flex items-center gap-1">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-4 h-4 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        <span class="font-medium text-gray-600 ml-1">4.9</span>
                        <span class="text-gray-400 ml-0.5">rating</span>
                    </div>
                </div>
            </div>

            {{-- Visual --}}
            <div class="relative flex items-center justify-center">
                <div class="relative w-full max-w-md mx-auto">
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 mx-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="w-14 h-14 btn-primary rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m0-3l-3-3m0 0l-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5a2.25 2.25 0 01-2.25-2.25v-.75"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-center text-sm font-semibold text-gray-700 mb-1">Drag & Drop Here</p>
                        <p class="text-center text-xs text-gray-400 mb-5">or click to browse your files</p>

                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:20px">
                            @foreach([
                                ['label' => 'PNG',  'bg' => '#ef4444'],
                                ['label' => 'JPG',  'bg' => '#eab308'],
                                ['label' => 'WEBP', 'bg' => '#22c55e'],
                                ['label' => 'PDF',  'bg' => '#2563eb'],
                            ] as $fmt)
                            <div style="display:flex;flex-direction:column;align-items:center;gap:6px">
                                <div class="rounded-xl shadow-sm format-icon flex items-center justify-center" style="width:40px;height:40px;background:{{ $fmt['bg'] }}">
                                    <span style="font-size:9px;font-weight:900;color:#fff">{{ $fmt['label'] }}</span>
                                </div>
                                <span class="text-gray-400 font-medium" style="font-size:10px">{{ $fmt['label'] }}</span>
                            </div>
                            @endforeach
                        </div>

                        <div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full w-3/4 btn-primary rounded-full"></div>
                            </div>
                            <div class="flex justify-between text-[10px] text-gray-400 mt-1.5">
                                <span>Converting…</span>
                                <span>75%</span>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -top-3 left-0 bg-white rounded-xl shadow-lg border border-gray-100 px-3 py-2 flex items-center gap-2">
                        <div class="w-7 h-7 bg-red-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-[8px] font-black text-white">PNG</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-700">PNG → JPG</p>
                            <p class="text-[9px] text-green-500 font-medium">✓ Done in 0.3s</p>
                        </div>
                    </div>

                    <div class="absolute -bottom-3 right-0 bg-white rounded-xl shadow-lg border border-gray-100 px-3 py-2 flex items-center gap-2">
                        <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-[8px] font-black text-white">PDF</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-700">JPG → PDF</p>
                            <p class="text-[9px] text-green-500 font-medium">✓ Done in 0.5s</p>
                        </div>
                    </div>

                    <div class="absolute top-1/3 -right-4 bg-white rounded-xl shadow-lg border border-gray-100 px-3 py-2 flex items-center gap-2">
                        <div class="w-7 h-7 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-[8px] font-black text-white">WEBP</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-700">PNG → WEBP</p>
                            <p class="text-[9px] text-blue-500 font-medium">API ready</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ QUICK CONVERSIONS ═══════════ --}}
<section class="bg-white py-6 border-y border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center gap-3">
            @foreach([
                ['from' => 'PNG', 'to' => 'JPG',  'color' => 'bg-red-50 text-red-700 border-red-200'],
                ['from' => 'JPG', 'to' => 'PNG',  'color' => 'bg-yellow-50 text-yellow-700 border-yellow-200'],
                ['from' => 'PNG', 'to' => 'WEBP', 'color' => 'bg-green-50 text-green-700 border-green-200'],
                ['from' => 'JPG', 'to' => 'WEBP', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                ['from' => 'PNG', 'to' => 'PDF',  'color' => 'bg-blue-50 text-blue-700 border-blue-200'],
                ['from' => 'JPG', 'to' => 'PDF',  'color' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            ] as $conv)
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 {{ $conv['color'] }} border rounded-full px-4 py-1.5 text-sm font-semibold hover:shadow-sm transition-all">
                {{ $conv['from'] }}
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                {{ $conv['to'] }}
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ FEATURES ═══════════ --}}
<section class="bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mb-4">Tools That Do More</h2>
            <p class="text-gray-500 text-lg max-w-xl mx-auto">Everything you need to handle image conversions — from a quick one-off to full API integration.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['icon'=>'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z','color'=>'bg-violet-100 text-violet-600','title'=>'Image Conversion','desc'=>'PNG, JPG, WEBP and PDF — convert between all supported formats in one click with quality presets.'],
                ['icon'=>'M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6','color'=>'bg-blue-100 text-blue-600','title'=>'Quality Control','desc'=>'Choose from Medium, High or Best quality. Control resize, background fill, metadata stripping and more.'],
                ['icon'=>'M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z','color'=>'bg-cyan-100 text-cyan-600','title'=>'REST API','desc'=>'Programmatic access on Pro and Max plans. Upload files, create conversions and download results via API.'],
                ['icon'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z','color'=>'bg-emerald-100 text-emerald-600','title'=>'Fast Processing','desc'=>'Conversions complete in under a second. Queue-backed processing ensures reliability even at scale.'],
                ['icon'=>'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z','color'=>'bg-rose-100 text-rose-600','title'=>'Secure & Private','desc'=>'Files are automatically deleted after the retention period. No file is stored longer than your plan allows.'],
                ['icon'=>'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z','color'=>'bg-orange-100 text-orange-600','title'=>'Conversion History','desc'=>'Every conversion is logged. Re-download results, track credit usage and review your full history.'],
            ] as $f)
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-11 h-11 {{ $f['color'] }} rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ FEATURE PILLS ═══════════ --}}
<section class="bg-white py-12 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center gap-4">
            @foreach([
                ['icon' => '🖼️', 'text' => '6 Formats Supported'],
                ['icon' => '⚡', 'text' => 'Sub-second Conversion'],
                ['icon' => '🔒', 'text' => 'Auto File Deletion'],
                ['icon' => '🔑', 'text' => 'API Access'],
                ['icon' => '💳', 'text' => 'Credit-Based Pricing'],
            ] as $pill)
            <div class="flex items-center gap-2.5 bg-gray-50 border border-gray-200 rounded-full px-5 py-2.5 text-sm font-medium text-gray-700">
                <span>{{ $pill['icon'] }}</span>
                {{ $pill['text'] }}
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ SUPPORTED FORMATS ═══════════ --}}
<section class="bg-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mb-3">Supported Formats</h2>
            <p class="text-gray-500 text-lg">The most common image formats, all in one place.</p>
        </div>
        <div class="flex flex-wrap justify-center gap-6 mb-8">
            @foreach([
                ['label' => 'PNG',  'bg' => '#ef4444', 'desc' => 'Lossless image'],
                ['label' => 'JPG',  'bg' => '#eab308', 'desc' => 'Web photo'],
                ['label' => 'WEBP', 'bg' => '#22c55e', 'desc' => 'Modern web'],
                ['label' => 'PDF',  'bg' => '#2563eb', 'desc' => 'Document'],
            ] as $fmt)
            <div class="flex flex-col items-center gap-2 cursor-default group">
                <div class="rounded-2xl shadow-md format-icon group-hover:scale-110 transition-transform flex items-center justify-center" style="width:64px;height:64px;background:{{ $fmt['bg'] }}">
                    <span class="text-xs font-black text-white tracking-wide">{{ $fmt['label'] }}</span>
                </div>
                <span class="text-xs font-semibold text-gray-700">{{ $fmt['label'] }}</span>
                <span class="text-gray-400" style="font-size:11px">{{ $fmt['desc'] }}</span>
            </div>
            @endforeach
        </div>
        <div class="text-center">
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-violet-600 hover:text-violet-700 transition-colors">
                View all supported conversions →
            </a>
        </div>
    </div>
</section>

{{-- ═══════════ STATS BAR ═══════════ --}}
<section class="stats-bg py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center text-white">
            @foreach([
                ['value' => '1k+',   'label' => 'Happy Users'],
                ['value' => '10k+',  'label' => 'Files Converted'],
                ['value' => '4.9/5', 'label' => 'Average Rating'],
                ['value' => '99.9%', 'label' => 'Uptime & Secure'],
            ] as $stat)
            <div>
                <div class="text-4xl font-extrabold mb-1">{{ $stat['value'] }}</div>
                <div class="text-sm text-white/75 font-medium">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ TESTIMONIALS ═══════════ --}}
<section class="bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 mb-2">
                Loved by People Worldwide <span class="text-rose-500">♥</span>
            </h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['text'=>'FileConverter is exactly what I needed. Clean interface, lightning fast, and the API integration took less than 10 minutes to set up.','name'=>'Michael T.','role'=>'Product Designer','initials'=>'MT','bg'=>'#7c3aed'],
                ['text'=>'I use it daily for batch image exports. PNG to WEBP conversions are super fast and the quality presets save me so much time.','name'=>'Priya S.','role'=>'Frontend Developer','initials'=>'PS','bg'=>'#3b82f6'],
                ['text'=>'Finally a converter that just works. No ads, no watermarks, automatic deletion. Love the credit system — only pay for what you use.','name'=>'Daniel K.','role'=>'Content Creator','initials'=>'DK','bg'=>'#10b981'],
            ] as $r)
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="text-violet-300 text-4xl font-serif leading-none mb-4">"</div>
                <p class="text-gray-600 text-sm leading-relaxed mb-6">{{ $r['text'] }}</p>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0" style="background:{{ $r['bg'] }}">{{ $r['initials'] }}</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $r['name'] }}</p>
                            <p class="text-xs text-gray-400">{{ $r['role'] }}</p>
                        </div>
                    </div>
                    <div class="flex gap-0.5">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-3.5 h-3.5 text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ CTA BANNER ═══════════ --}}
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-violet-50 via-blue-50 to-cyan-50 border border-violet-100 rounded-3xl p-10 flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 btn-primary rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-1">Ready to Convert Your Files?</h3>
                    <p class="text-gray-500 text-sm">Join users who trust FileConverter for their image conversion needs.</p>
                </div>
            </div>
            <div class="flex flex-col items-center lg:items-end gap-1.5 flex-shrink-0">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 text-base font-semibold text-white btn-primary px-7 py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all whitespace-nowrap">
                        Go to Dashboard →
                    </a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 text-base font-semibold text-white btn-primary px-7 py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all whitespace-nowrap">
                        Get Started Free →
                    </a>
                    <span class="text-xs text-gray-400">No credit card required</span>
                @endauth
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ FOOTER ═══════════ --}}
<footer class="bg-white border-t border-gray-100 py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
            <div class="col-span-2">
                <a href="/" class="flex items-center gap-2.5 mb-4">
                    <div class="w-8 h-8 rounded-xl btn-primary flex items-center justify-center shadow-sm flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m0-3l-3-3m0 0l-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5a2.25 2.25 0 01-2.25-2.25v-.75"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-gray-900">File<span class="bg-gradient-to-r from-violet-600 to-blue-500 bg-clip-text text-transparent">Converter</span></span>
                </a>
                <p class="text-sm text-gray-400 leading-relaxed max-w-xs">The all-in-one image converter. Fast, secure and free to try.</p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Product</h4>
                <ul class="space-y-3 text-sm text-gray-400">
                    <li><a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition-colors">Convert</a></li>
                    <li><a href="{{ route('docs.api') }}" class="hover:text-gray-600 transition-colors">API</a></li>
                    <li><a href="{{ route('history') }}" class="hover:text-gray-600 transition-colors">History</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Account</h4>
                <ul class="space-y-3 text-sm text-gray-400">
                    <li><a href="{{ route('billing') }}" class="hover:text-gray-600 transition-colors">Pricing</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-gray-600 transition-colors">Sign in</a></li>
                    @if (Route::has('register'))
                    <li><a href="{{ route('register') }}" class="hover:text-gray-600 transition-colors">Register</a></li>
                    @endif
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-4">Resources</h4>
                <ul class="space-y-3 text-sm text-gray-400">
                    <li><a href="{{ route('docs.api') }}" class="hover:text-gray-600 transition-colors">API Docs</a></li>
                    <li><a href="{{ route('settings') }}" class="hover:text-gray-600 transition-colors">Settings</a></li>
                </ul>
            </div>
        </div>
        <div class="pt-8 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-400">© {{ date('Y') }} FileConverter. All rights reserved.</p>
            <p class="text-xs text-gray-300">Built with Laravel & Tailwind CSS</p>
        </div>
    </div>
</footer>

</body>
</html>
