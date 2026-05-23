<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
@php
    // Derive the URL prefix for this CMS site so internal links work
    // both on the central /site/{tenant}/... path and on tenant
    // subdomains where the prefix is empty.
    $tenantId = function_exists('tenant') ? optional(tenant())->id : null;
    $siteBase = $tenantId && \Illuminate\Support\Str::startsWith(request()->getPathInfo(), '/site/')
        ? '/site/' . $tenantId
        : '';
    $applyBase = $siteBase ? $siteBase . '/apply' : '/apply';
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="{{ $settings->primary_color ?? '#1a56db' }}">
    <meta name="description" content="{{ $metaDescription ?? ($settings->tagline ?? '') }}">
    <title>@yield('title', $settings->school_name ?? 'School')</title>
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="icon" href="{{ $settings->favicon_path ? asset('storage/' . $settings->favicon_path) : asset('favicon.ico') }}">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->school_name ?? 'School' }}">
    <meta property="og:title" content="@yield('title', $settings->school_name ?? 'School')">
    <meta property="og:description" content="{{ $metaDescription ?? ($settings->tagline ?? '') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($settings->logo_path)
        <meta property="og:image" content="{{ asset('storage/' . $settings->logo_path) }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $settings->school_name ?? 'School')">
    <meta name="twitter:description" content="{{ $metaDescription ?? ($settings->tagline ?? '') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $settings->primary_color ?? "#1a56db" }}',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>:root { --primary: {{ $settings->primary_color ?? '#1a56db' }}; }</style>
    {{-- Alpine.js (used by hero slider, mobile drawer, etc.) --}}
    <script defer src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js"></script>
    {{-- Tailwind Typography plugin (for prose classes) --}}
    <script src="https://unpkg.com/@tailwindcss/typography@0.5.10/dist/typography.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        /* Brand design language (matches KynexEdu landing page) */
        :root {
            --grad-brand: linear-gradient(135deg,#4f46e5 0%,#2563eb 55%,#06b6d4 100%);
            --grad-hero:  linear-gradient(135deg,#1e1b4b,#1e3a8a,#2563eb,#0ea5e9);
        }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        .grad-brand { background-image: var(--grad-brand); }
        .grad-hero  { background-image: var(--grad-hero); }
        .grad-text {
            background-image: var(--grad-brand);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; color: transparent;
        }

        /* Scroll reveal */
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
        .reveal.is-in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .reveal { opacity: 1; transform: none; transition: none; }
            *, *::before, *::after { animation-duration: .001ms !important; }
        }
    </style>
    @stack('styles')

    {{-- Structured data: the school as an EducationalOrganization --}}
    @php
        $cmsLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => $settings->school_name ?? null,
            'url' => url($siteBase ?: '/'),
            'description' => $settings->tagline ?? null,
            'logo' => $settings->logo_path ? asset('storage/' . $settings->logo_path) : null,
            'telephone' => $settings->phone ?? null,
            'email' => $settings->email ?? null,
            'address' => ($settings->address ?? null) ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->address,
            ] : null,
            'sameAs' => array_values(array_filter([
                $settings->facebook_url ?? null,
                $settings->twitter_url ?? null,
                $settings->instagram_url ?? null,
                $settings->youtube_url ?? null,
            ])),
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    @endphp
    <script type="application/ld+json">{!! json_encode($cmsLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body class="font-sans antialiased text-slate-800 bg-white">

    {{-- Top Bar --}}
    <div class="grad-hero text-white text-sm">
        <div class="max-w-7xl mx-auto px-4 py-2 flex flex-wrap justify-between items-center gap-y-1">
            <div class="flex items-center gap-4 flex-wrap">
                @if($settings->phone)
                    <a href="tel:{{ $settings->phone }}" class="flex items-center gap-1.5 hover:opacity-80 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>{{ $settings->phone }}</span>
                    </a>
                @endif
                @if($settings->email)
                    <a href="mailto:{{ $settings->email }}" class="flex items-center gap-1.5 hover:opacity-80 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>{{ $settings->email }}</span>
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-2.5">
                @if($settings->facebook_url)
                    <a href="{{ $settings->facebook_url }}" target="_blank" rel="noopener" aria-label="Facebook" class="hover:opacity-80 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.9 3.78-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.44 2.9h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94z"/></svg>
                    </a>
                @endif
                @if($settings->twitter_url)
                    <a href="{{ $settings->twitter_url }}" target="_blank" rel="noopener" aria-label="X (Twitter)" class="hover:opacity-80 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                    </a>
                @endif
                @if($settings->instagram_url)
                    <a href="{{ $settings->instagram_url }}" target="_blank" rel="noopener" aria-label="Instagram" class="hover:opacity-80 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.73 3.73 0 01-1.38-.9 3.73 3.73 0 01-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 1.62c-3.15 0-3.52.01-4.76.07-1.15.05-1.77.24-2.19.4-.55.22-.94.47-1.35.88-.41.41-.66.8-.88 1.35-.16.42-.35 1.04-.4 2.19-.06 1.24-.07 1.61-.07 4.76s.01 3.52.07 4.76c.05 1.15.24 1.77.4 2.19.22.55.47.94.88 1.35.41.41.8.66 1.35.88.42.16 1.04.35 2.19.4 1.24.06 1.61.07 4.76.07s3.52-.01 4.76-.07c1.15-.05 1.77-.24 2.19-.4.55-.22.94-.47 1.35-.88.41-.41.66-.8.88-1.35.16-.42.35-1.04.4-2.19.06-1.24.07-1.61.07-4.76s-.01-3.52-.07-4.76c-.05-1.15-.24-1.77-.4-2.19a3.64 3.64 0 00-.88-1.35 3.64 3.64 0 00-1.35-.88c-.42-.16-1.04-.35-2.19-.4-1.24-.06-1.61-.07-4.76-.07zm0 2.76a5.46 5.46 0 110 10.92 5.46 5.46 0 010-10.92zm0 9a3.54 3.54 0 100-7.08 3.54 3.54 0 000 7.08zm6.95-9.22a1.27 1.27 0 11-2.55 0 1.27 1.27 0 012.55 0z"/></svg>
                    </a>
                @endif
                @if($settings->youtube_url)
                    <a href="{{ $settings->youtube_url }}" target="_blank" rel="noopener" aria-label="YouTube" class="hover:opacity-80 transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3.02 3.02 0 00-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.51A3.02 3.02 0 00.5 6.2C0 8.07 0 12 0 12s0 3.93.5 5.8a3.02 3.02 0 002.12 2.14c1.88.51 9.38.51 9.38.51s7.5 0 9.38-.51a3.02 3.02 0 002.12-2.14C24 15.93 24 12 24 12s0-3.93-.5-5.8zM9.55 15.57V8.43L15.82 12l-6.27 3.57z"/></svg>
                    </a>
                @endif
                <a href="/admin" class="ml-1 inline-flex items-center min-h-[28px] bg-white/95 text-primary px-3 py-1 rounded-full text-xs font-bold hover:bg-white transition">Portal Login</a>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav x-data="{ open: false }"
         class="bg-white/90 backdrop-blur-md shadow-sm border-b border-slate-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="{{ $siteBase ?: '/' }}" class="flex items-center gap-3 min-h-[44px]">
                    @if($settings->logo_path)
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->school_name }}" class="h-10 w-auto">
                    @else
                        <span class="grad-brand w-10 h-10 rounded-xl flex items-center justify-center text-white font-extrabold text-lg shadow-md shrink-0">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($settings->school_name ?? 'S', 0, 1)) }}
                        </span>
                    @endif
                    <span class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900">{{ $settings->school_name ?? 'School' }}</span>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden lg:flex items-center gap-1">
                    <a href="{{ $siteBase ?: '/' }}" class="px-3 py-2 rounded-lg text-slate-700 hover:text-primary hover:bg-slate-50 font-medium transition">Home</a>
                    <a href="{{ $siteBase }}/about" class="px-3 py-2 rounded-lg text-slate-700 hover:text-primary hover:bg-slate-50 font-medium transition">About</a>
                    <a href="{{ $siteBase }}/admissions" class="px-3 py-2 rounded-lg text-slate-700 hover:text-primary hover:bg-slate-50 font-medium transition">Admissions</a>
                    <a href="{{ $siteBase }}/gallery" class="px-3 py-2 rounded-lg text-slate-700 hover:text-primary hover:bg-slate-50 font-medium transition">Gallery</a>
                    <a href="{{ $siteBase }}/news" class="px-3 py-2 rounded-lg text-slate-700 hover:text-primary hover:bg-slate-50 font-medium transition">News</a>
                    <a href="{{ $siteBase }}/results" class="px-3 py-2 rounded-lg text-slate-700 hover:text-primary hover:bg-slate-50 font-medium transition">Results</a>
                    <a href="{{ $siteBase }}/contact" class="px-3 py-2 rounded-lg text-slate-700 hover:text-primary hover:bg-slate-50 font-medium transition">Contact</a>
                    <a href="{{ $applyBase }}" class="ml-2 grad-brand text-white px-5 py-2.5 rounded-xl font-semibold shadow-md hover:shadow-xl hover:-translate-y-0.5 transition">Apply Now</a>
                </div>

                {{-- Mobile Menu Button --}}
                <button @click="open = true" type="button"
                        class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition"
                        aria-label="Open menu" :aria-expanded="open">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile Slide-in Drawer --}}
        <div x-cloak
             @keydown.escape.window="open = false"
             class="lg:hidden">
            {{-- Backdrop --}}
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="open = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60]"></div>

            {{-- Drawer panel --}}
            <aside x-show="open"
                   x-transition:enter="transition ease-out duration-300"
                   x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition ease-in duration-200"
                   x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                   class="fixed top-0 right-0 h-[100dvh] w-[86vw] max-w-sm bg-white z-[70] shadow-2xl flex flex-col"
                   aria-label="Mobile navigation">
                <div class="flex items-center justify-between px-5 h-16 border-b border-slate-100">
                    <span class="text-lg font-extrabold tracking-tight text-slate-900">{{ $settings->school_name ?? 'School' }}</span>
                    <button @click="open = false" type="button"
                            class="inline-flex items-center justify-center w-11 h-11 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition"
                            aria-label="Close menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto px-3 py-4 flex flex-col gap-1">
                    <a href="{{ $siteBase ?: '/' }}" class="flex items-center min-h-[48px] px-4 rounded-xl text-slate-700 hover:text-primary hover:bg-slate-50 font-medium text-base transition">Home</a>
                    <a href="{{ $siteBase }}/about" class="flex items-center min-h-[48px] px-4 rounded-xl text-slate-700 hover:text-primary hover:bg-slate-50 font-medium text-base transition">About</a>
                    <a href="{{ $siteBase }}/admissions" class="flex items-center min-h-[48px] px-4 rounded-xl text-slate-700 hover:text-primary hover:bg-slate-50 font-medium text-base transition">Admissions</a>
                    <a href="{{ $siteBase }}/gallery" class="flex items-center min-h-[48px] px-4 rounded-xl text-slate-700 hover:text-primary hover:bg-slate-50 font-medium text-base transition">Gallery</a>
                    <a href="{{ $siteBase }}/news" class="flex items-center min-h-[48px] px-4 rounded-xl text-slate-700 hover:text-primary hover:bg-slate-50 font-medium text-base transition">News</a>
                    <a href="{{ $siteBase }}/results" class="flex items-center min-h-[48px] px-4 rounded-xl text-slate-700 hover:text-primary hover:bg-slate-50 font-medium text-base transition">Results</a>
                    <a href="{{ $siteBase }}/contact" class="flex items-center min-h-[48px] px-4 rounded-xl text-slate-700 hover:text-primary hover:bg-slate-50 font-medium text-base transition">Contact</a>
                </nav>
                <div class="px-5 py-5 border-t border-slate-100 flex flex-col gap-2.5">
                    <a href="{{ $applyBase }}" class="grad-brand text-white text-center px-5 py-3 rounded-xl font-semibold shadow-md min-h-[48px] flex items-center justify-center">Apply for Admission</a>
                    <a href="/admin" class="text-center px-5 py-3 rounded-xl font-semibold border border-slate-200 text-primary hover:bg-slate-50 transition min-h-[48px] flex items-center justify-center">Portal Login</a>
                </div>
            </aside>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-400 mt-20">
        <div class="max-w-7xl mx-auto px-4 py-14">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                {{-- About --}}
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        @if($settings->logo_path)
                            <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->school_name }}" class="h-10 w-auto">
                        @else
                            <span class="grad-brand w-10 h-10 rounded-xl flex items-center justify-center text-white font-extrabold text-lg shrink-0">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($settings->school_name ?? 'S', 0, 1)) }}
                            </span>
                        @endif
                        <h3 class="text-white text-xl font-extrabold tracking-tight">{{ $settings->school_name ?? 'School' }}</h3>
                    </div>
                    <p class="text-sm leading-relaxed max-w-md">{{ Str::limit(strip_tags($settings->about_text ?? ''), 200) }}</p>
                    {{-- Social icons --}}
                    <div class="flex items-center gap-3 mt-5">
                        @if($settings->facebook_url)
                            <a href="{{ $settings->facebook_url }}" target="_blank" rel="noopener" aria-label="Facebook" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-300 hover:text-white transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.9 3.78-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.44 2.9h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94z"/></svg>
                            </a>
                        @endif
                        @if($settings->twitter_url)
                            <a href="{{ $settings->twitter_url }}" target="_blank" rel="noopener" aria-label="X (Twitter)" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-300 hover:text-white transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                            </a>
                        @endif
                        @if($settings->instagram_url)
                            <a href="{{ $settings->instagram_url }}" target="_blank" rel="noopener" aria-label="Instagram" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-300 hover:text-white transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.73 3.73 0 01-1.38-.9 3.73 3.73 0 01-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 4.32a5.46 5.46 0 110 10.92 5.46 5.46 0 010-10.92zm0 9a3.54 3.54 0 100-7.08 3.54 3.54 0 000 7.08zm6.95-9.22a1.27 1.27 0 11-2.55 0 1.27 1.27 0 012.55 0z"/></svg>
                            </a>
                        @endif
                        @if($settings->youtube_url)
                            <a href="{{ $settings->youtube_url }}" target="_blank" rel="noopener" aria-label="YouTube" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-300 hover:text-white transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3.02 3.02 0 00-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.51A3.02 3.02 0 00.5 6.2C0 8.07 0 12 0 12s0 3.93.5 5.8a3.02 3.02 0 002.12 2.14c1.88.51 9.38.51 9.38.51s7.5 0 9.38-.51a3.02 3.02 0 002.12-2.14C24 15.93 24 12 24 12s0-3.93-.5-5.8zM9.55 15.57V8.43L15.82 12l-6.27 3.57z"/></svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-widest mb-4">Quick Links</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ $siteBase }}/about" class="hover:text-white transition">About Us</a></li>
                        <li><a href="{{ $siteBase }}/admissions" class="hover:text-white transition">Admissions</a></li>
                        <li><a href="{{ $applyBase }}" class="hover:text-white transition">Apply Online</a></li>
                        <li><a href="{{ $siteBase }}/results" class="hover:text-white transition">Results</a></li>
                        <li><a href="{{ $siteBase }}/contact" class="hover:text-white transition">Contact</a></li>
                        <li><a href="/admin" class="hover:text-white transition">Portal Login</a></li>
                    </ul>
                </div>

                {{-- Contact Info --}}
                <div>
                    <h4 class="text-white font-bold text-xs uppercase tracking-widest mb-4">Contact Us</h4>
                    <ul class="space-y-2.5 text-sm">
                        @if($settings->address)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>{{ $settings->address }}</span>
                            </li>
                        @endif
                        @if($settings->phone)
                            <li><a href="tel:{{ $settings->phone }}" class="hover:text-white transition">{{ $settings->phone }}</a></li>
                        @endif
                        @if($settings->email)
                            <li><a href="mailto:{{ $settings->email }}" class="hover:text-white transition break-all">{{ $settings->email }}</a></li>
                        @endif
                        @if($settings->whatsapp)
                            <li><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->whatsapp) }}" target="_blank" rel="noopener" class="hover:text-white transition">WhatsApp: {{ $settings->whatsapp }}</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 mt-10 pt-6 text-sm text-center text-slate-500">
                <p>&copy; {{ date('Y') }} {{ $settings->school_name ?? 'School' }}. All rights reserved. Powered by <a href="https://kynexsolution.com" target="_blank" rel="noopener" class="text-blue-400 hover:text-blue-300 transition font-semibold">KynexEdu</a></p>
            </div>
        </div>
    </footer>

    {{-- WhatsApp Float --}}
    @if($settings->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->whatsapp) }}" target="_blank" rel="noopener"
           class="fixed bottom-6 right-6 bg-green-500 text-white p-4 rounded-full shadow-lg hover:bg-green-600 hover:scale-105 transition z-40"
           aria-label="Chat on WhatsApp" title="Chat on WhatsApp">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
    @endif

    {{-- Scroll reveal (IntersectionObserver, reduced-motion aware) --}}
    <script>
        (function () {
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var els = document.querySelectorAll('.reveal');
            if (reduce || !('IntersectionObserver' in window)) {
                els.forEach(function (el) { el.classList.add('is-in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            els.forEach(function (el) { io.observe(el); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
