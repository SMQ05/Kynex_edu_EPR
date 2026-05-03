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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $metaDescription ?? ($settings->tagline ?? '') }}">
    <title>@yield('title', $settings->school_name ?? 'School')</title>
    <link rel="icon" href="{{ $settings->favicon_path ? asset('storage/' . $settings->favicon_path) : asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '{{ $settings->primary_color ?? "#1a56db" }}',
                    }
                }
            }
        }
    </script>
    <style>:root { --primary: {{ $settings->primary_color ?? '#1a56db' }}; }</style>
    {{-- Alpine.js (used by hero slider, mobile menu, etc.) --}}
    <script defer src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js"></script>
    {{-- Tailwind Typography plugin (for prose classes) --}}
    <script src="https://unpkg.com/@tailwindcss/typography@0.5.10/dist/typography.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-800 bg-white">

    {{-- Top Bar --}}
    <div class="bg-primary text-white text-sm">
        <div class="max-w-7xl mx-auto px-4 py-2 flex flex-wrap justify-between items-center">
            <div class="flex items-center gap-4">
                @if($settings->phone)
                    <a href="tel:{{ $settings->phone }}" class="flex items-center gap-1 hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $settings->phone }}
                    </a>
                @endif
                @if($settings->email)
                    <a href="mailto:{{ $settings->email }}" class="flex items-center gap-1 hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $settings->email }}
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($settings->facebook_url)
                    <a href="{{ $settings->facebook_url }}" target="_blank" class="hover:opacity-80">FB</a>
                @endif
                @if($settings->twitter_url)
                    <a href="{{ $settings->twitter_url }}" target="_blank" class="hover:opacity-80">TW</a>
                @endif
                @if($settings->instagram_url)
                    <a href="{{ $settings->instagram_url }}" target="_blank" class="hover:opacity-80">IG</a>
                @endif
                @if($settings->youtube_url)
                    <a href="{{ $settings->youtube_url }}" target="_blank" class="hover:opacity-80">YT</a>
                @endif
                <a href="/admin" class="ml-2 bg-white text-primary px-3 py-1 rounded text-xs font-semibold hover:bg-gray-100">Portal Login</a>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="{{ $siteBase ?: '/' }}" class="flex items-center gap-3">
                    @if($settings->logo_path)
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->school_name }}" class="h-10 w-auto">
                    @endif
                    <span class="text-xl font-bold text-primary">{{ $settings->school_name ?? 'School' }}</span>
                </a>

                {{-- Desktop Nav --}}
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ $siteBase ?: '/' }}" class="text-gray-700 hover:text-primary font-medium transition">Home</a>
                    <a href="{{ $siteBase }}/about" class="text-gray-700 hover:text-primary font-medium transition">About</a>
                    <a href="{{ $siteBase }}/admissions" class="text-gray-700 hover:text-primary font-medium transition">Admissions</a>
                    <a href="{{ $siteBase }}/gallery" class="text-gray-700 hover:text-primary font-medium transition">Gallery</a>
                    <a href="{{ $siteBase }}/news" class="text-gray-700 hover:text-primary font-medium transition">News</a>
                    <a href="{{ $siteBase }}/results" class="text-gray-700 hover:text-primary font-medium transition">Results</a>
                    <a href="{{ $siteBase }}/contact" class="text-gray-700 hover:text-primary font-medium transition">Contact</a>
                    <a href="{{ $applyBase }}" class="bg-primary text-white px-4 py-2 rounded font-medium hover:opacity-90">Apply for Admission</a>
                </div>

                {{-- Mobile Menu Button --}}
                <button id="mobile-menu-btn" class="md:hidden p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile Nav --}}
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-4 py-3 space-y-2">
                <a href="{{ $siteBase ?: '/' }}" class="block py-2 text-gray-700 hover:text-primary">Home</a>
                <a href="{{ $siteBase }}/about" class="block py-2 text-gray-700 hover:text-primary">About</a>
                <a href="{{ $siteBase }}/admissions" class="block py-2 text-gray-700 hover:text-primary">Admissions</a>
                <a href="{{ $siteBase }}/gallery" class="block py-2 text-gray-700 hover:text-primary">Gallery</a>
                <a href="{{ $siteBase }}/news" class="block py-2 text-gray-700 hover:text-primary">News</a>
                <a href="{{ $siteBase }}/results" class="block py-2 text-gray-700 hover:text-primary">Results</a>
                <a href="{{ $siteBase }}/contact" class="block py-2 text-gray-700 hover:text-primary">Contact</a>
                <a href="{{ $applyBase }}" class="block py-2 font-semibold text-primary">Apply for Admission →</a>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-300 mt-16">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                {{-- About --}}
                <div class="md:col-span-2">
                    <h3 class="text-white text-lg font-bold mb-3">{{ $settings->school_name ?? 'School' }}</h3>
                    <p class="text-sm leading-relaxed">{{ Str::limit(strip_tags($settings->about_text ?? ''), 200) }}</p>
                </div>

                {{-- Quick Links --}}
                <div>
                    <h4 class="text-white font-semibold mb-3">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
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
                    <h4 class="text-white font-semibold mb-3">Contact Us</h4>
                    <ul class="space-y-2 text-sm">
                        @if($settings->address)
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $settings->address }}
                            </li>
                        @endif
                        @if($settings->phone)
                            <li><a href="tel:{{ $settings->phone }}" class="hover:text-white">{{ $settings->phone }}</a></li>
                        @endif
                        @if($settings->email)
                            <li><a href="mailto:{{ $settings->email }}" class="hover:text-white">{{ $settings->email }}</a></li>
                        @endif
                        @if($settings->whatsapp)
                            <li><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->whatsapp) }}" target="_blank" class="hover:text-white">WhatsApp: {{ $settings->whatsapp }}</a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-6 text-sm text-center">
                <p>&copy; {{ date('Y') }} {{ $settings->school_name ?? 'School' }}. All rights reserved. Powered by <a href="https://kynexsolution.com" target="_blank" class="text-blue-400 hover:text-blue-300">KynexEdu</a></p>
            </div>
        </div>
    </footer>

    {{-- WhatsApp Float --}}
    @if($settings->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->whatsapp) }}" target="_blank"
           class="fixed bottom-6 right-6 bg-green-500 text-white p-4 rounded-full shadow-lg hover:bg-green-600 transition z-40"
           title="Chat on WhatsApp">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
    @endif

    <script>
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu')?.classList.toggle('hidden');
        });
    </script>
    @stack('scripts')
</body>
</html>
