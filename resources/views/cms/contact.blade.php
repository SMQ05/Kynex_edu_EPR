@extends('cms.layout')

@section('title', 'Contact — ' . ($settings->school_name ?? 'School'))

@section('content')
{{-- Header --}}
<section class="relative grad-hero text-white py-20 sm:py-24 overflow-hidden">
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-6 text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">Contact us</h1>
        <p class="mt-4 text-lg text-white/85">We'd love to hear from you.</p>
        <nav class="mt-5 text-sm text-white/70">
            <a href="{{ $siteBase ?: '/' }}" class="hover:text-white transition">Home</a>
            <span class="mx-2">/</span>
            <span class="text-white">Contact</span>
        </nav>
    </div>
</section>

{{-- Contact cards --}}
<section class="py-14 sm:py-16 bg-white">
    <div class="max-w-7xl mx-auto px-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- Address --}}
        @if ($settings->address)
            <div class="reveal rounded-2xl bg-slate-50 border border-slate-100 p-7 text-center hover:shadow-xl hover:-translate-y-1 transition">
                <div class="w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center text-2xl mx-auto mb-4 shadow-md">📍</div>
                <h3 class="font-bold text-slate-900">Address</h3>
                <p class="mt-2 text-sm text-slate-500">{{ $settings->address }}</p>
            </div>
        @endif

        {{-- Phone --}}
        @if ($settings->phone)
            <div class="reveal rounded-2xl bg-slate-50 border border-slate-100 p-7 text-center hover:shadow-xl hover:-translate-y-1 transition">
                <div class="w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center text-2xl mx-auto mb-4 shadow-md">☎️</div>
                <h3 class="font-bold text-slate-900">Phone</h3>
                <a href="tel:{{ $settings->phone }}" class="mt-2 inline-block text-primary hover:underline font-semibold">{{ $settings->phone }}</a>
                @if ($settings->whatsapp)
                    <div class="mt-1"><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->whatsapp) }}" target="_blank" rel="noopener" class="text-emerald-600 text-sm hover:underline font-medium">WhatsApp →</a></div>
                @endif
            </div>
        @endif

        {{-- Email --}}
        @if ($settings->email)
            <div class="reveal rounded-2xl bg-slate-50 border border-slate-100 p-7 text-center hover:shadow-xl hover:-translate-y-1 transition">
                <div class="w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center text-2xl mx-auto mb-4 shadow-md">✉️</div>
                <h3 class="font-bold text-slate-900">Email</h3>
                <a href="mailto:{{ $settings->email }}" class="mt-2 inline-block text-primary hover:underline font-semibold break-all">{{ $settings->email }}</a>
            </div>
        @endif
    </div>
</section>

{{-- Contact form + map (stacked on mobile, side-by-side on desktop) --}}
<section class="py-16 sm:py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-8 items-start">
        {{-- Form --}}
        <div class="reveal rounded-3xl bg-white p-8 shadow-md border border-slate-100">
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Send us a message</h2>
            <p class="mt-1.5 text-sm text-slate-500">Fill the form below and we'll get back to you.</p>

            @if (session('success'))
                <div class="mt-5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    ✓ {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mt-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">
                    Please fix the highlighted fields and try again.
                </div>
            @endif

            <form method="POST" action="{{ $siteBase ? $siteBase . '/contact-form' : '/contact-form' }}" class="mt-6 space-y-4">
                @csrf
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Your name *</label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                               class="w-full min-h-[44px] rounded-xl border-slate-200 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email *</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                               class="w-full min-h-[44px] rounded-xl border-slate-200 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone (optional)</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}"
                               class="w-full min-h-[44px] rounded-xl border-slate-200 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Subject *</label>
                        <input type="text" name="subject" required value="{{ old('subject') }}"
                               class="w-full min-h-[44px] rounded-xl border-slate-200 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Message *</label>
                    <textarea name="message" rows="5" required
                              class="w-full rounded-xl border-slate-200 shadow-sm focus:border-primary focus:ring-primary text-sm">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="inline-flex items-center justify-center min-h-[48px] grad-brand text-white px-7 py-3 rounded-xl font-semibold shadow-md hover:-translate-y-0.5 hover:shadow-xl transition">
                    Send message
                </button>
            </form>
        </div>

        {{-- Map / info column --}}
        <div class="reveal space-y-6">
            @if (filled($settings->address_map_iframe))
                <div class="rounded-3xl overflow-hidden shadow-md border border-slate-100 aspect-[16/12] sm:aspect-[16/10]">
                    {!! $settings->address_map_iframe !!}
                </div>
            @else
                <div class="rounded-3xl grad-hero text-white p-8 sm:p-10 shadow-md relative overflow-hidden">
                    <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-cyan-400/20 blur-3xl"></div>
                    <div class="relative">
                        <h3 class="text-2xl font-extrabold tracking-tight">Get in touch</h3>
                        <p class="mt-2 text-white/85">We're here to answer your questions. Reach out through any of the channels below.</p>
                        <ul class="mt-6 space-y-3 text-sm">
                            @if ($settings->address)
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ $settings->address }}</span>
                                </li>
                            @endif
                            @if ($settings->phone)
                                <li class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <a href="tel:{{ $settings->phone }}" class="hover:underline">{{ $settings->phone }}</a>
                                </li>
                            @endif
                            @if ($settings->email)
                                <li class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <a href="mailto:{{ $settings->email }}" class="hover:underline break-all">{{ $settings->email }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
