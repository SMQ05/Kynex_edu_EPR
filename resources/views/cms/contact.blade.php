@extends('cms.layout')

@section('title', 'Contact — ' . ($settings->school_name ?? 'School'))

@section('content')
<section class="bg-gradient-to-r from-primary to-blue-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <h1 class="text-4xl md:text-5xl font-bold">Contact us</h1>
        <p class="mt-3 text-lg text-white/85">We'd love to hear from you.</p>
        <nav class="mt-4 text-sm text-white/70">
            <a href="{{ $siteBase ?: '/' }}" class="hover:underline">Home</a>
            <span class="mx-2">/</span>
            <span>Contact</span>
        </nav>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-3 gap-8">
        {{-- Address --}}
        @if ($settings->address)
            <div class="rounded-xl bg-gray-50 p-6 text-center">
                <div class="text-3xl mb-2">📍</div>
                <h3 class="font-semibold text-gray-900">Address</h3>
                <p class="mt-2 text-sm text-gray-600">{{ $settings->address }}</p>
            </div>
        @endif

        {{-- Phone --}}
        @if ($settings->phone)
            <div class="rounded-xl bg-gray-50 p-6 text-center">
                <div class="text-3xl mb-2">☎️</div>
                <h3 class="font-semibold text-gray-900">Phone</h3>
                <a href="tel:{{ $settings->phone }}" class="mt-2 inline-block text-primary hover:underline font-medium">{{ $settings->phone }}</a>
                @if ($settings->whatsapp)
                    <div class="mt-1"><a href="https://wa.me/{{ $settings->whatsapp }}" target="_blank" class="text-emerald-600 text-sm hover:underline">WhatsApp →</a></div>
                @endif
            </div>
        @endif

        {{-- Email --}}
        @if ($settings->email)
            <div class="rounded-xl bg-gray-50 p-6 text-center">
                <div class="text-3xl mb-2">✉️</div>
                <h3 class="font-semibold text-gray-900">Email</h3>
                <a href="mailto:{{ $settings->email }}" class="mt-2 inline-block text-primary hover:underline font-medium break-all">{{ $settings->email }}</a>
            </div>
        @endif
    </div>
</section>

{{-- Contact form --}}
<section class="py-16 bg-gray-50">
    <div class="max-w-3xl mx-auto px-6">
        <div class="rounded-2xl bg-white p-8 shadow-md">
            <h2 class="text-2xl font-bold text-gray-900">Send us a message</h2>
            <p class="mt-1 text-sm text-gray-600">Fill the form below and we'll get back to you.</p>

            @if (session('success'))
                <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    ✓ {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mt-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm">
                    Please fix the highlighted fields and try again.
                </div>
            @endif

            <form method="POST" action="{{ $siteBase ? $siteBase . '/contact-form' : '/contact-form' }}" class="mt-6 space-y-4">
                @csrf
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Your name *</label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" name="email" required value="{{ old('email') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Subject *</label>
                        <input type="text" name="subject" required value="{{ old('subject') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Message *</label>
                    <textarea name="message" rows="5" required
                              class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg font-semibold hover:opacity-90 transition">
                    Send message
                </button>
            </form>
        </div>
    </div>
</section>

{{-- Map --}}
@if (filled($settings->address_map_iframe))
    <section class="bg-gray-100">
        <div class="max-w-7xl mx-auto px-6 pb-16">
            <div class="rounded-2xl overflow-hidden shadow-md aspect-[16/7]">
                {!! $settings->address_map_iframe !!}
            </div>
        </div>
    </section>
@endif
@endsection
