<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'File Converter') }} — Convert Any File, Instantly</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=geist:300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        /* ── Header ── */
        .nav-brand {
            font-size: 24px; font-weight: 900; color: #111827; letter-spacing: -1px;
            font-family: 'Geist', ui-sans-serif, system-ui, sans-serif;
        }
        .nav-brand-gradient {
            background: linear-gradient(90deg, #ec4899 0%, #a855f7 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .nav-link {
            font-size: 14px; font-weight: 500; color: #374151;
            display: flex; align-items: center; gap: 4px;
            transition: color .15s; white-space: nowrap;
        }
        .nav-link:hover { color: #111827; }
        .nav-chevron {
            width: 14px; height: 14px; color: #9ca3af;
        }
        .nav-badge-new {
            font-size: 10px; font-weight: 700; color: #fff;
            background: linear-gradient(90deg, #ec4899 0%, #f97316 100%);
            border-radius: 20px; padding: 1px 7px; line-height: 18px;
            display: inline-block;
        }
        .nav-lang {
            display: flex; align-items: center; gap: 5px;
            font-size: 14px; font-weight: 500; color: #374151;
            border: 1px solid #e5e7eb; border-radius: 8px;
            padding: 6px 10px; cursor: pointer; transition: border-color .15s;
        }
        .nav-lang:hover { border-color: #d1d5db; }
        .nav-signin {
            font-size: 14px; font-weight: 500; color: #374151;
            transition: color .15s; white-space: nowrap;
        }
        .nav-signin:hover { color: #111827; }
        .nav-btn-signup {
            font-size: 14px; font-weight: 600; color: #fff;
            background: linear-gradient(90deg, #ec4899 0%, #f97316 100%);
            border-radius: 10px; padding: 8px 18px;
            white-space: nowrap; transition: opacity .15s;
        }
        .nav-btn-signup:hover { opacity: 0.9; }
        /* ── Rest ── */
        .hero-bg {
            background: radial-gradient(ellipse 80% 60% at 60% 40%, rgba(139,92,246,0.12) 0%, transparent 60%),
                        radial-gradient(ellipse 50% 50% at 85% 20%, rgba(251,146,60,0.10) 0%, transparent 50%),
                        radial-gradient(ellipse 60% 40% at 10% 70%, rgba(59,130,246,0.08) 0%, transparent 50%),
                        #ffffff;
        }
        /* ── Pixel-perfect sections (from plans/1.html) ── */
        .px-sec {
            font-family: 'Geist', ui-sans-serif, system-ui, sans-serif;
            color: #1a1a2e; letter-spacing: -0.16px;
            background: #fbfbff;
        }
        .px-container { max-width: 1200px; margin: 0 auto; padding: 30px 16px 40px; }
        .px-h2 {
            font-size: 22px; font-weight: 700; letter-spacing: -0.44px;
            color: #1a1a2e; text-align: center; margin: 16px 0 22px;
        }
        .px-grad-text {
            background: linear-gradient(120deg, #7c3aed, #d946ef);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .fmt-grid { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 10px; }
        @media (min-width: 1024px) { .fmt-grid { grid-template-columns: repeat(15, minmax(0,1fr)); } }
        .stats-grid { display: grid; gap: 18px; grid-template-columns: minmax(0,1fr); }
        @media (min-width: 640px)  { .stats-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4, minmax(0,1fr)); } }
        .testi-grid { display: grid; gap: 14px; grid-template-columns: minmax(0,1fr); }
        @media (min-width: 1024px) { .testi-grid { grid-template-columns: repeat(3, minmax(0,1fr)); } }
        .tools-slider-wrap { position: relative; }
        .tools-slider {
            display: flex; gap: 14px; overflow-x: auto;
            scroll-snap-type: x mandatory; padding-bottom: 4px;
        }
        .no-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .tools-card {
            position: relative; display: flex; flex-direction: column; gap: 10px;
            flex: 0 0 232px; scroll-snap-align: start;
            padding: 18px; border-radius: 16px; border: 1px solid #efedf7;
        }
        .tools-nav {
            position: absolute; top: 50%; transform: translateY(-50%); z-index: 2;
            display: flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 999px;
            background: #fff; border: 1px solid #e8e8f0; color: #5a5a72;
            box-shadow: rgba(0,0,0,0.12) 0px 4px 12px -4px; cursor: pointer;
            transition: color .15s, box-shadow .15s;
        }
        .tools-nav:hover { color: #1a1a2e; box-shadow: rgba(0,0,0,0.18) 0px 6px 16px -4px; }
        .tools-nav-prev { left: -16px; }
        .tools-nav-next { right: -16px; }
        .cta-card {
            display: flex; flex-direction: column; align-items: center; gap: 20px;
            padding: 18px 22px; border-radius: 18px; background: #fff;
            box-shadow: rgba(0,0,0,0.1) 0px 8px 24px -16px;
        }
        .cta-text { flex: 1; text-align: center; }
        .cta-right { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
        @media (min-width: 1024px) {
            .cta-card { flex-direction: row; }
            .cta-text { text-align: left; }
            .cta-right { align-items: flex-end; }
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
        @media (min-width: 1024px) {
            .hero-grid { grid-template-columns: 1fr 1.06fr; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased font-sans">

{{-- ═══════════ NAVIGATION ═══════════ --}}
<header style="position:sticky;top:0;z-index:50;background:rgba(255,255,255,0.97);backdrop-filter:blur(12px);border-bottom:1px solid #f3f4f6;">
    <div style="max-width:1280px;margin:0 auto;padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:60px;">

        {{-- Logo --}}
        <a href="/" style="display:flex;align-items:center;text-decoration:none;">
            <span class="nav-brand">File<span class="nav-brand-gradient">Converter</span></span>
        </a>

        {{-- Nav links --}}
        <nav style="display:flex;align-items:center;gap:28px;">
            <a href="{{ route('docs.api') }}" class="nav-link" style="gap:7px;">
                API
                <span class="nav-badge-new">New</span>
            </a>

            <a href="{{ route('billing') }}" class="nav-link">
                Pricing
            </a>

            @auth
                <a href="{{ route('history') }}" class="nav-link">
                    History
                </a>

                <a href="{{ route('settings') }}" class="nav-link">
                    Settings
                    <svg class="nav-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </a>
            @endauth
        </nav>

        {{-- Right controls --}}
        <div style="display:flex;align-items:center;gap:10px;">
            {{-- Language --}}
            <div class="nav-lang">
                <svg style="width:15px;height:15px;color:#9ca3af;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                <span style="font-size:14px;font-weight:500;color:#374151;">EN</span>
                <svg style="width:13px;height:13px;color:#9ca3af" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </div>

            @auth
                <a href="{{ url('/dashboard') }}" class="nav-btn-signup" style="text-decoration:none;">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-signin" style="text-decoration:none;padding:0 4px;">Sign in</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="nav-btn-signup" style="text-decoration:none;">Sign up free</a>
                @endif
            @endauth
        </div>
    </div>
</header>

{{-- ═══════════ HERO ═══════════ --}}
<section class="hero-bg overflow-hidden">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-16">
        <div class="grid hero-grid gap-10 lg:gap-6 items-start">

            {{-- ── LEFT: Headline + social proof ── --}}
            <div>
                {{-- Badge --}}
                <div style="display:inline-flex;align-items:center;gap:8px;border:1.5px solid #c4b5fd;border-radius:999px;padding:7px 16px;margin-bottom:22px;background:rgba(237,233,254,0.45);">
                    <svg style="width:15px;height:15px;color:#7c3aed;flex-shrink:0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span style="font-size:13px;font-weight:600;color:#5b21b6;letter-spacing:0;">Instant File Conversion</span>
                </div>

                {{-- Headline: slightly smaller, bolder, tight --}}
                <h1 style="font-size:clamp(40px,4.3vw,58px);font-weight:900;line-height:1.05;letter-spacing:-1.5px;margin-bottom:18px;font-family:'Geist',ui-sans-serif,system-ui,sans-serif;white-space:nowrap;">
                    <span style="color:#7c3aed;display:block;">Any File.</span>
                    <span style="color:#111827;display:block;">Any Format.</span>
                    <span style="background:linear-gradient(90deg,#ec4899 0%,#f97316 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;display:block;">Converted Fast.</span>
                </h1>

                {{-- Subtitle --}}
                <p style="font-size:17px;line-height:1.6;letter-spacing:-0.2px;color:#6b7280;margin-bottom:28px;">
                    <span class="lg:block">Convert files between formats in seconds — images, documents and more.</span>
                    <span class="lg:block">Fast, secure, and incredibly simple.</span>
                </p>

                {{-- Social proof --}}
                <div style="display:flex;align-items:center;gap:16px;">
                    {{-- Overlapping avatars --}}
                    <div style="display:flex;">
                        @foreach(range(1, 5) as $i)
                        <img
                            src="{{ asset('images/avatars/avatar-'.$i.'.jpg') }}"
                            alt=""
                            width="38" height="38"
                            style="width:38px;height:38px;border-radius:50%;border:2.5px solid #fff;object-fit:cover;{{ $i > 1 ? 'margin-left:-10px;' : '' }}position:relative;z-index:{{ 11 - $i }};">
                        @endforeach
                    </div>
                    {{-- Stars + text --}}
                    <div>
                        <div style="display:flex;align-items:center;gap:3px;margin-bottom:2px;">
                            @for($i = 0; $i < 5; $i++)
                            <svg style="width:15px;height:15px;fill:#f59e0b" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                            <span style="font-size:15px;font-weight:700;color:#111827;margin-left:4px;">4.9</span>
                        </div>
                        <span style="font-size:12px;color:#9ca3af;font-weight:500;">Trusted by 1k+ users worldwide</span>
                    </div>
                </div>
            </div>

            {{-- ── RIGHT: Converter form (real for both auth and guest users) ── --}}
            <div>
                @auth
                    @livewire('dashboard.dashboard-converter')
                @else
                    @livewire('guest-converter')
                @endauth
            </div>

        </div>
    </div>
</section>

{{-- ═══════════ TOOLS + SUPPORTED FORMATS + STATS + TESTIMONIALS + CTA (pixel-perfect) ═══════════ --}}
<section class="px-sec">
<div class="px-container">

    {{-- Tools That Do More (slider) --}}
    <div>
        <h2 style="font-size:24px;font-weight:700;letter-spacing:-0.48px;color:#1a1a2e;text-align:center;margin:8px 0 22px;">
            <span class="px-grad-text">Tools</span> That Do <span style="color:#f97316;">More</span>
        </h2>
        <div x-data class="tools-slider-wrap">
            <button type="button" class="tools-nav tools-nav-prev" aria-label="Previous tools"
                @click="$refs.toolsSlider.scrollBy({left:-492,behavior:'smooth'})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </button>
            <div x-ref="toolsSlider" class="tools-slider no-scrollbar">
                @foreach([
                    ['title'=>'File Conversion','desc'=>'Convert PNG, JPG, WEBP and PDF between formats in one click.','pill'=>'One click','new'=>true,
                     'card'=>'linear-gradient(rgb(255,245,230),rgb(255,234,204))','icon_bg'=>'linear-gradient(135deg,#f97316,#f59e0b)','accent'=>'#a26420','title_c'=>'#ea580c',
                     'icon'=>'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25z'],
                    ['title'=>'Quality Control','desc'=>'Medium, High or Best presets. Resize, background fill and metadata.','pill'=>'3 presets','new'=>false,
                     'card'=>'linear-gradient(rgb(230,249,248),rgb(208,240,238))','icon_bg'=>'linear-gradient(135deg,#5fe4d2,#71deff)','accent'=>'#0f766e','title_c'=>'#0f766e',
                     'icon'=>'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75'],
                    ['title'=>'REST API','desc'=>'Upload files, create conversions and download results via API.','pill'=>'Pro & Max','new'=>false,
                     'card'=>'linear-gradient(rgb(238,244,255),rgb(223,233,255))','icon_bg'=>'linear-gradient(135deg,#4eaade,#3877c5)','accent'=>'#3a5cdc','title_c'=>'#1d4ed8',
                     'icon'=>'M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5'],
                    ['title'=>'Fast Processing','desc'=>'Conversions complete in under a second, queue-backed at scale.','pill'=>'Sub-second','new'=>false,
                     'card'=>'linear-gradient(rgb(245,239,255),rgb(236,223,255))','icon_bg'=>'linear-gradient(135deg,#a78bfa,#7c3aed)','accent'=>'#6d28d9','title_c'=>'#6d28d9',
                     'icon'=>'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z'],
                    ['title'=>'Secure & Private','desc'=>'Files are automatically deleted after your plan\'s retention period.','pill'=>'Auto-delete','new'=>false,
                     'card'=>'linear-gradient(rgb(248,238,254),rgb(239,220,251))','icon_bg'=>'linear-gradient(135deg,#d946ef,#a855f7)','accent'=>'#a21caf','title_c'=>'#a21caf',
                     'icon'=>'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                    ['title'=>'Conversion History','desc'=>'Every conversion logged. Re-download results and track credits.','pill'=>'Full log','new'=>false,
                     'card'=>'linear-gradient(rgb(255,238,242),rgb(255,224,232))','icon_bg'=>'linear-gradient(135deg,#fb7185,#e11d48)','accent'=>'#be123c','title_c'=>'#be123c',
                     'icon'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                ] as $t)
                <div class="tools-card" style="background:{{ $t['card'] }};">
                    @if ($t['new'])
                        <span style="position:absolute;top:12px;right:12px;padding:2px 8px;border-radius:999px;background:linear-gradient(135deg,#ffb547,#f97316);font-size:10px;font-weight:700;letter-spacing:-0.1px;color:#fff;">New</span>
                    @endif
                    <div style="display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:14px;background:{{ $t['icon_bg'] }};box-shadow:rgba(0,0,0,0.3) 0px 6px 14px -6px, rgba(255,255,255,0.3) 0px 1px 0px 0px inset;">
                        <svg style="width:26px;height:26px;color:#fff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $t['icon'] }}"/>
                        </svg>
                    </div>
                    <div style="margin-top:6px;font-size:14.5px;font-weight:700;letter-spacing:-0.145px;color:{{ $t['title_c'] }};">{{ $t['title'] }}</div>
                    <div style="font-size:12px;line-height:16.8px;color:#5a5a72;flex:1;">{{ $t['desc'] }}</div>
                    <div style="display:inline-flex;align-items:center;gap:5px;align-self:flex-start;padding:5px 10px;border-radius:999px;background:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.9);font-size:11.5px;font-weight:600;color:{{ $t['accent'] }};">
                        {{ $t['pill'] }}
                    </div>
                </div>
                @endforeach
            </div>
            <button type="button" class="tools-nav tools-nav-next" aria-label="Next tools"
                @click="$refs.toolsSlider.scrollBy({left:492,behavior:'smooth'})">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </button>
        </div>
    </div>

    {{-- 100+ Formats Supported --}}
    <div style="margin-top:14px;">
        <h2 class="px-h2"><span class="px-grad-text">100+</span> Formats Supported</h2>
        <div style="background:#fff;border:1px solid #e8e8f0;border-radius:16px;padding:20px 16px;">
            <div class="fmt-grid">
                @foreach([
                    ['label' => 'PDF',  'from' => '#ff6b6b', 'to' => '#dc2626', 'sh' => 'rgba(220,38,38,0.33)'],
                    ['label' => 'DOCX', 'from' => '#3b82f6', 'to' => '#1d4ed8', 'sh' => 'rgba(29,78,216,0.33)'],
                    ['label' => 'XLSX', 'from' => '#22c55e', 'to' => '#15803d', 'sh' => 'rgba(21,128,61,0.33)'],
                    ['label' => 'PPTX', 'from' => '#f97316', 'to' => '#c2410c', 'sh' => 'rgba(194,65,12,0.33)'],
                    ['label' => 'JPG',  'from' => '#a855f7', 'to' => '#7e22ce', 'sh' => 'rgba(126,34,206,0.33)'],
                    ['label' => 'PNG',  'from' => '#06b6d4', 'to' => '#0e7490', 'sh' => 'rgba(14,116,144,0.33)'],
                    ['label' => 'GIF',  'from' => '#10b981', 'to' => '#047857', 'sh' => 'rgba(4,120,87,0.33)'],
                    ['label' => 'MP4',  'from' => '#ef4444', 'to' => '#b91c1c', 'sh' => 'rgba(185,28,28,0.33)'],
                    ['label' => 'MOV',  'from' => '#ec4899', 'to' => '#be185d', 'sh' => 'rgba(190,24,93,0.33)'],
                    ['label' => 'MP3',  'from' => '#fbbf24', 'to' => '#d97706', 'sh' => 'rgba(217,119,6,0.33)'],
                    ['label' => 'WAV',  'from' => '#fb923c', 'to' => '#c2410c', 'sh' => 'rgba(194,65,12,0.33)'],
                    ['label' => 'ZIP',  'from' => '#eab308', 'to' => '#a16207', 'sh' => 'rgba(161,98,7,0.33)'],
                    ['label' => 'RAR',  'from' => '#ca8a04', 'to' => '#854d0e', 'sh' => 'rgba(133,77,14,0.33)'],
                    ['label' => 'TXT',  'from' => '#64748b', 'to' => '#334155', 'sh' => 'rgba(51,65,85,0.33)'],
                ] as $fmt)
                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <div style="display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:9px;background:linear-gradient({{ $fmt['from'] }},{{ $fmt['to'] }});box-shadow:{{ $fmt['sh'] }} 0px 4px 12px -4px, rgba(255,255,255,0.4) 0px 1px 0px 0px inset;">
                        <span style="font-size:11px;font-weight:800;letter-spacing:-0.44px;color:#fff;">{{ $fmt['label'] }}</span>
                    </div>
                    <span style="font-size:11px;font-weight:500;color:#5a5a72;">{{ $fmt['label'] }}</span>
                </div>
                @endforeach
                {{-- +85 More --}}
                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <div style="display:flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:9px;border:1px dashed #d4d4e0;background:#fbfaff;">
                        <span style="font-size:11px;font-weight:700;color:#1a1a2e;">+85</span>
                    </div>
                    <span style="font-size:11px;font-weight:500;color:#5a5a72;">More</span>
                </div>
            </div>
            <div style="text-align:center;margin-top:16px;">
                <a href="{{ route('dashboard') }}" style="font-size:12.5px;font-weight:600;color:#7c3aed;text-decoration:none;">
                    View all supported formats →
                </a>
            </div>
        </div>
    </div>

    {{-- Stats bar --}}
    <div style="margin-top:24px;">
        <div class="stats-grid" style="padding:26px 30px;border-radius:22px;background:linear-gradient(90deg,#7c3aed 0%,#d946ef 50%,#f97316 100%);box-shadow:rgba(217,70,239,0.33) 0px 20px 50px -20px;">
            @foreach([
                ['value' => '1k+',   'label' => 'Happy Users',     'icon' => 'M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z'],
                ['value' => '10k+',  'label' => 'Files Converted', 'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z'],
                ['value' => '4.9/5', 'label' => 'Average Rating',  'icon' => 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z'],
                ['value' => '99.9%', 'label' => 'Uptime & Secure', 'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z'],
            ] as $stat)
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:999px;background:rgba(255,255,255,0.18);flex-shrink:0;">
                    <svg style="width:20px;height:20px;color:#fff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:28px;font-weight:800;line-height:28px;letter-spacing:-0.84px;color:#fff;">{{ $stat['value'] }}</div>
                    <div style="font-size:12.5px;color:rgba(255,255,255,0.9);margin-top:4px;">{{ $stat['label'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Testimonials --}}
    <div style="margin-top:28px;">
        <h2 class="px-h2" style="margin:16px 0 24px;display:flex;align-items:center;justify-content:center;gap:8px;">
            Loved by People Worldwide
            {{-- Hand-drawn heart outline + squiggle --}}
            <svg width="52" height="28" viewBox="0 0 52 28" fill="none" aria-hidden="true">
                <path d="M16 24S4 17.5 4 9.8C4 5.9 7.1 3 10.7 3c2.2 0 4.2 1.2 5.3 3 1.1-1.8 3.1-3 5.3-3C24.9 3 28 5.9 28 9.8 28 17.5 16 24 16 24Z" stroke="#f472b6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M32 20c3-2.5 5 2 8 -0.5s5 1.5 8-1" stroke="#f9a8d4" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </h2>
        <div class="testi-grid">
            @foreach([
                ['text'=>'FileConverter is exactly what I needed. Clean interface, lightning fast, and the API integration took less than 10 minutes to set up.','name'=>'Michael T.','role'=>'Product Designer','photo'=>'avatar-1.jpg','accent'=>'#7c3aed','star'=>'#8b5cf6'],
                ['text'=>'I use it daily for batch image exports. PNG to WEBP conversions are super fast and the quality presets save me so much time.','name'=>'Priya S.','role'=>'Frontend Developer','photo'=>'avatar-2.jpg','accent'=>'#f97316','star'=>'#f59e0b'],
                ['text'=>'Finally a converter that just works. No ads, no watermarks, automatic deletion. Love the credit system — only pay for what you use.','name'=>'Daniel K.','role'=>'Content Creator','photo'=>'avatar-5.jpg','accent'=>'#14b8a6','star'=>'#14b8a6'],
            ] as $r)
            <div style="display:flex;flex-direction:column;gap:14px;padding:20px;border-radius:18px;background:#fff;border:1px solid #e8e8f0;box-shadow:rgba(0,0,0,0.12) 0px 8px 24px -16px;">
                <div style="display:flex;align-items:flex-start;gap:12px;">
                    {{-- "66" quote mark --}}
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="{{ $r['accent'] }}" aria-hidden="true" style="flex-shrink:0;margin-top:1px;">
                        <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 0 1-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179Zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311 1.804.167 3.226 1.648 3.226 3.489a3.5 3.5 0 0 1-3.5 3.5c-1.073 0-2.099-.49-2.748-1.179Z"/>
                    </svg>
                    <p style="font-size:13px;line-height:19.5px;letter-spacing:-0.065px;color:#1a1a2e;margin:0;">{{ $r['text'] }}</p>
                </div>
                <div style="display:flex;align-items:center;gap:12px;margin-top:auto;">
                    <img src="{{ asset('images/avatars/'.$r['photo']) }}" alt="" width="44" height="44" style="width:44px;height:44px;border-radius:999px;object-fit:cover;border:2px solid #fff;box-shadow:rgba(0,0,0,0.18) 0px 2px 6px -2px;flex-shrink:0;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:700;color:#1a1a2e;">{{ $r['name'] }}</div>
                        <div style="font-size:11.5px;color:#5a5a72;">{{ $r['role'] }}</div>
                    </div>
                    <div style="display:flex;gap:2px;flex-shrink:0;">
                        @for($i = 0; $i < 5; $i++)
                        <svg width="15" height="15" viewBox="0 0 20 20" fill="{{ $r['star'] }}"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- CTA banner --}}
    <div style="margin-top:22px;">
        <div class="cta-card">
            <div style="display:flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#d946ef);flex-shrink:0;">
                <svg style="width:28px;height:28px;color:#fff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                </svg>
            </div>
            <div class="cta-text">
                <div style="font-size:18px;font-weight:700;letter-spacing:-0.36px;color:#1a1a2e;">Ready to Supercharge Your Workflow?</div>
                <div style="font-size:13px;color:#5a5a72;margin-top:2px;">Join users who trust FileConverter for their file conversion needs.</div>
            </div>
            <div class="cta-right">
                @auth
                    <a href="{{ url('/dashboard') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;height:38px;padding:0 16px;border-radius:10px;background:linear-gradient(135deg,#7c3aed 0%,#d946ef 50%,#f97316 100%);font-size:14px;font-weight:600;letter-spacing:-0.14px;color:#fff;text-decoration:none;white-space:nowrap;box-shadow:rgba(255,255,255,0.35) 0px 1px 0px 0px inset, rgba(217,70,239,0.4) 0px 8px 24px -8px;">
                        Go to Dashboard <span aria-hidden="true">→</span>
                    </a>
                @else
                    <a href="{{ route('register') }}" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;height:38px;padding:0 16px;border-radius:10px;background:linear-gradient(135deg,#7c3aed 0%,#d946ef 50%,#f97316 100%);font-size:14px;font-weight:600;letter-spacing:-0.14px;color:#fff;text-decoration:none;white-space:nowrap;box-shadow:rgba(255,255,255,0.35) 0px 1px 0px 0px inset, rgba(217,70,239,0.4) 0px 8px 24px -8px;">
                        Get Started Free <span aria-hidden="true">→</span>
                    </a>
                    <div style="font-size:11px;color:#9696aa;">No credit card required</div>
                @endauth
            </div>
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
                <p class="text-sm text-gray-400 leading-relaxed max-w-xs">The all-in-one file converter. Fast, secure and free to try.</p>
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
