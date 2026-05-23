@extends('cms.layout')

@section('title', 'About — ' . ($settings->school_name ?? 'School'))

@section('content')
{{-- Page header --}}
<section class="relative grad-hero text-white py-20 sm:py-24 overflow-hidden">
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-6 text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">About {{ $settings->school_name ?? 'us' }}</h1>
        @if ($settings->tagline)
            <p class="mt-4 text-lg text-white/85 max-w-2xl mx-auto">{{ $settings->tagline }}</p>
        @endif
        <nav class="mt-5 text-sm text-white/70">
            <a href="{{ $siteBase ?: '/' }}" class="hover:text-white transition">Home</a>
            <span class="mx-2">/</span>
            <span class="text-white">About</span>
        </nav>
    </div>
</section>

{{-- About text + image --}}
<section class="py-16 sm:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
        <div class="reveal">
            @if ($settings->about_image_path)
                <img src="{{ asset('storage/' . $settings->about_image_path) }}" alt="" class="rounded-3xl w-full h-96 object-cover shadow-xl">
            @else
                <div class="rounded-3xl h-96 grad-brand opacity-90 flex items-center justify-center">
                    @if ($settings->logo_path)
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" class="max-h-48" alt="">
                    @endif
                </div>
            @endif
        </div>
        <div class="reveal">
            <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">Our story</span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">A school built on excellence</h2>
            <div class="mt-5 prose prose-lg text-slate-600 max-w-none">{!! $settings->about_text ?? '<em>About content will appear here once added in CMS Settings.</em>' !!}</div>
        </div>
    </div>
</section>

{{-- Vision + Mission --}}
@if (filled($settings->vision_text) || filled($settings->mission_text))
    <section class="py-16 sm:py-20 bg-slate-50">
        <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-6">
            @if (filled($settings->vision_text))
                <div class="reveal rounded-3xl bg-white p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition border-t-4 border-primary">
                    <div class="w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center text-2xl shadow-md">🎯</div>
                    <h3 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">Our Vision</h3>
                    <p class="mt-3 text-slate-600 leading-relaxed">{{ $settings->vision_text }}</p>
                </div>
            @endif
            @if (filled($settings->mission_text))
                <div class="reveal rounded-3xl bg-white p-8 shadow-sm hover:shadow-xl hover:-translate-y-1 transition border-t-4 border-cyan-500">
                    <div class="w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center text-2xl shadow-md">🚀</div>
                    <h3 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900">Our Mission</h3>
                    <p class="mt-3 text-slate-600 leading-relaxed">{{ $settings->mission_text }}</p>
                </div>
            @endif
        </div>
    </section>
@endif

{{-- Principal --}}
@if (filled($settings->principal_message))
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-5xl mx-auto px-6">
            <div class="reveal rounded-3xl bg-gradient-to-br from-primary/5 to-cyan-50 p-8 md:p-12 grid md:grid-cols-3 gap-8 items-center border border-slate-100">
                <div class="md:col-span-1 text-center">
                    @if ($settings->principal_photo_path)
                        <img src="{{ asset('storage/' . $settings->principal_photo_path) }}" alt="Principal"
                             class="w-44 h-44 rounded-full object-cover mx-auto shadow-xl ring-4 ring-white">
                    @else
                        <div class="w-44 h-44 rounded-full grad-brand mx-auto opacity-90 ring-4 ring-white shadow-xl"></div>
                    @endif
                    <div class="mt-4 font-bold text-lg text-slate-900">{{ $settings->principal_name ?? 'Principal' }}</div>
                    <div class="text-xs text-primary uppercase tracking-widest font-semibold">Principal</div>
                </div>
                <div class="md:col-span-2">
                    <h3 class="text-2xl font-extrabold tracking-tight text-slate-900">A message from our principal</h3>
                    <div class="mt-4 prose prose-sm text-slate-700 max-w-none">{!! $settings->principal_message !!}</div>
                </div>
            </div>
        </div>
    </section>
@endif

{{-- CTA --}}
<section class="py-16 sm:py-20 grad-hero text-white relative overflow-hidden">
    <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-indigo-500/20 blur-3xl"></div>
    <div class="relative max-w-4xl mx-auto px-6 text-center reveal">
        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Want to know more?</h2>
        <p class="mt-3 text-white/85 text-lg">Visit us, ask a question, or apply for admission today.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="{{ $siteBase }}/contact" class="inline-flex items-center justify-center min-h-[48px] bg-white text-primary px-7 py-3 rounded-xl font-semibold shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition">Contact Us</a>
            @if ($settings->admission_open ?? false)
                <a href="{{ $applyBase }}" class="inline-flex items-center justify-center min-h-[48px] bg-white/10 border border-white/40 backdrop-blur text-white px-7 py-3 rounded-xl font-semibold hover:bg-white/20 transition">Apply Online</a>
            @endif
        </div>
    </div>
</section>
@endsection
