@extends('cms.layout')

@section('title', 'About Us - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">About Us</h1>
            <p class="text-lg text-white/80">{{ $settings->tagline ?? 'Excellence in Education' }}</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-primary">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">About Us</span>
            </nav>
        </div>
    </div>

    {{-- About Content --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4">
            @if($settings->about_text)
                <div class="max-w-4xl mx-auto">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Our Story</h2>
                    <div class="w-16 h-1 bg-primary mb-8"></div>
                    <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                        {!! $settings->about_text !!}
                    </div>
                </div>
            @else
                <div class="text-center text-gray-500 py-12">
                    <p>About information will be available soon.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Principal's Message --}}
    @if($settings->principal_message)
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Principal's Message</h2>
                    <div class="w-16 h-1 bg-primary mx-auto"></div>
                </div>
                <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-sm p-8 md:p-12">
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <div class="shrink-0 text-center">
                            @if($settings->principal_photo_path)
                                <img src="{{ asset('storage/' . $settings->principal_photo_path) }}"
                                     alt="{{ $settings->principal_name }}"
                                     class="w-48 h-48 rounded-xl object-cover shadow-md">
                            @else
                                <div class="w-48 h-48 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <svg class="w-24 h-24 text-primary/30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                            @endif
                            <h3 class="text-lg font-bold text-gray-900 mt-4">{{ $settings->principal_name ?? 'Principal' }}</h3>
                            <p class="text-sm text-primary font-medium">Principal</p>
                        </div>
                        <div class="flex-1">
                            <svg class="w-10 h-10 text-primary/20 mb-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
                            <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                                {!! $settings->principal_message !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Mission/Vision/Values --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white border rounded-xl p-8 text-center hover:shadow-md transition">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Our Vision</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">To be a leading institution that nurtures future leaders through holistic education, innovation, and moral values.</p>
                </div>
                <div class="bg-white border rounded-xl p-8 text-center hover:shadow-md transition">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Our Mission</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">To provide quality education that develops critical thinking, creativity, and character in every student, preparing them for the challenges of tomorrow.</p>
                </div>
                <div class="bg-white border rounded-xl p-8 text-center hover:shadow-md transition">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Our Values</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Integrity, Excellence, Respect, Innovation, and Community — these are the pillars that guide everything we do.</p>
                </div>
            </div>
        </div>
    </section>

@endsection
