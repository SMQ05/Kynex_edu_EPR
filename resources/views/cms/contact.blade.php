@extends('cms.layout')

@section('title', 'Contact Us - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">Contact Us</h1>
            <p class="text-lg text-white/80">We'd love to hear from you</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-primary">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">Contact</span>
            </nav>
        </div>
    </div>

    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                {{-- Contact Info --}}
                <div class="lg:col-span-1">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Get In Touch</h2>

                    <div class="space-y-6">
                        @if($settings->address)
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Address</h3>
                                    <p class="text-gray-600 text-sm mt-1">{{ $settings->address }}</p>
                                </div>
                            </div>
                        @endif

                        @if($settings->phone)
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Phone</h3>
                                    <a href="tel:{{ $settings->phone }}" class="text-gray-600 text-sm mt-1 hover:text-primary">{{ $settings->phone }}</a>
                                </div>
                            </div>
                        @endif

                        @if($settings->email)
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Email</h3>
                                    <a href="mailto:{{ $settings->email }}" class="text-gray-600 text-sm mt-1 hover:text-primary">{{ $settings->email }}</a>
                                </div>
                            </div>
                        @endif

                        @if($settings->whatsapp)
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">WhatsApp</h3>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->whatsapp) }}" 
                                       target="_blank"
                                       class="text-gray-600 text-sm mt-1 hover:text-green-600">{{ $settings->whatsapp }}</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Social Links --}}
                    @if($settings->facebook_url || $settings->twitter_url || $settings->instagram_url || $settings->youtube_url)
                        <div class="mt-8 pt-8 border-t">
                            <h3 class="font-semibold text-gray-900 mb-4">Follow Us</h3>
                            <div class="flex gap-3">
                                @if($settings->facebook_url)
                                    <a href="{{ $settings->facebook_url }}" target="_blank"
                                       class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-200 transition">
                                        <span class="text-sm font-bold">FB</span>
                                    </a>
                                @endif
                                @if($settings->twitter_url)
                                    <a href="{{ $settings->twitter_url }}" target="_blank"
                                       class="w-10 h-10 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center hover:bg-sky-200 transition">
                                        <span class="text-sm font-bold">TW</span>
                                    </a>
                                @endif
                                @if($settings->instagram_url)
                                    <a href="{{ $settings->instagram_url }}" target="_blank"
                                       class="w-10 h-10 rounded-lg bg-pink-100 text-pink-600 flex items-center justify-center hover:bg-pink-200 transition">
                                        <span class="text-sm font-bold">IG</span>
                                    </a>
                                @endif
                                @if($settings->youtube_url)
                                    <a href="{{ $settings->youtube_url }}" target="_blank"
                                       class="w-10 h-10 rounded-lg bg-red-100 text-red-600 flex items-center justify-center hover:bg-red-200 transition">
                                        <span class="text-sm font-bold">YT</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Contact Form --}}
                <div class="lg:col-span-2">
                    <div class="bg-white border rounded-xl p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Send us a Message</h2>

                        <form action="/contact" method="POST" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('name') border-red-500 @enderror"
                                           placeholder="Your full name">
                                    @error('name')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('email') border-red-500 @enderror"
                                           placeholder="your@email.com">
                                    @error('email')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                                           placeholder="03XX-XXXXXXX">
                                </div>
                                <div>
                                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                                    <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('subject') border-red-500 @enderror"
                                           placeholder="What is this about?">
                                    @error('subject')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                                <textarea name="message" id="message" rows="6" required
                                          class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition @error('message') border-red-500 @enderror"
                                          placeholder="Write your message here...">{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                    class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition w-full md:w-auto">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

@endsection
