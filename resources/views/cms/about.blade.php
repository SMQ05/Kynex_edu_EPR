@extends('cms.layout')

@section('title', 'About — ' . ($settings->school_name ?? 'School'))

@section('content')
{{-- Page header --}}
<section class="bg-gradient-to-r from-primary to-blue-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <h1 class="text-4xl md:text-5xl font-bold">About {{ $settings->school_name ?? 'us' }}</h1>
        @if ($settings->tagline)
            <p class="mt-3 text-lg text-white/85">{{ $settings->tagline }}</p>
        @endif
        <nav class="mt-4 text-sm text-white/70">
            <a href="{{ $siteBase ?: '/' }}" class="hover:underline">Home</a>
            <span class="mx-2">/</span>
            <span>About</span>
        </nav>
    </div>
</section>

{{-- About text + image --}}
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
        <div>
            @if ($settings->about_image_path)
                <img src="{{ asset('storage/' . $settings->about_image_path) }}" alt="" class="rounded-2xl w-full h-96 object-cover shadow-xl">
            @else
                <div class="rounded-2xl h-96 bg-gradient-to-br from-primary/30 to-primary/5 flex items-center justify-center">
                    @if ($settings->logo_path)
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" class="max-h-48">
                    @endif
                </div>
            @endif
        </div>
        <div>
            <div class="text-sm uppercase tracking-widest text-primary font-semibold">Our story</div>
            <h2 class="mt-2 text-3xl font-bold text-gray-900">A school built on excellence</h2>
            <div class="mt-4 prose prose-lg text-gray-600 max-w-none">{!! $settings->about_text ?? '<em>About content will appear here once added in CMS Settings.</em>' !!}</div>
        </div>
    </div>
</section>

{{-- Vision + Mission --}}
@if (filled($settings->vision_text) || filled($settings->mission_text))
    <section class="py-16 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-6">
            @if (filled($settings->vision_text))
                <div class="rounded-2xl bg-white p-8 shadow-sm border-t-4 border-primary">
                    <div class="text-3xl">🎯</div>
                    <h3 class="mt-2 text-2xl font-bold text-gray-900">Our Vision</h3>
                    <p class="mt-3 text-gray-600 leading-relaxed">{{ $settings->vision_text }}</p>
                </div>
            @endif
            @if (filled($settings->mission_text))
                <div class="rounded-2xl bg-white p-8 shadow-sm border-t-4 border-blue-500">
                    <div class="text-3xl">🚀</div>
                    <h3 class="mt-2 text-2xl font-bold text-gray-900">Our Mission</h3>
                    <p class="mt-3 text-gray-600 leading-relaxed">{{ $settings->mission_text }}</p>
                </div>
            @endif
        </div>
    </section>
@endif

{{-- Principal --}}
@if (filled($settings->principal_message))
    <section class="py-16 bg-white">
        <div class="max-w-5xl mx-auto px-6">
            <div class="rounded-2xl bg-gradient-to-br from-primary/5 to-blue-50 p-8 md:p-12 grid md:grid-cols-3 gap-8 items-center">
                <div class="md:col-span-1 text-center">
                    @if ($settings->principal_photo_path)
                        <img src="{{ asset('storage/' . $settings->principal_photo_path) }}" alt="Principal"
                             class="w-44 h-44 rounded-full object-cover mx-auto shadow-xl ring-4 ring-white">
                    @endif
                    <div class="mt-3 font-semibold text-lg text-gray-900">{{ $settings->principal_name ?? 'Principal' }}</div>
                    <div class="text-xs text-primary uppercase tracking-widest font-semibold">Principal</div>
                </div>
                <div class="md:col-span-2">
                    <h3 class="text-2xl font-bold text-gray-900">A message from our principal</h3>
                    <div class="mt-3 prose prose-sm text-gray-700 max-w-none">{!! $settings->principal_message !!}</div>
                </div>
            </div>
        </div>
    </section>
@endif

{{-- CTA --}}
<section class="py-16 bg-primary text-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-2xl md:text-3xl font-bold">Want to know more?</h2>
        <p class="mt-2 text-white/85">Visit us, ask a question, or apply for admission today.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ $siteBase }}/contact" class="bg-white text-primary px-6 py-3 rounded-lg font-semibold hover:bg-gray-100">Contact Us</a>
            @if ($settings->admission_open ?? false)
                <a href="{{ $applyBase }}" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition">Apply Online</a>
            @endif
        </div>
    </div>
</section>
@endsection
