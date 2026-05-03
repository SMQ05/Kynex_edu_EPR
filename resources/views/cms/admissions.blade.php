@extends('cms.layout')

@section('title', 'Admissions — ' . ($settings->school_name ?? 'School'))

@section('content')
{{-- Hero --}}
<section class="bg-gradient-to-r from-primary to-blue-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <h1 class="text-4xl md:text-5xl font-bold">Admissions</h1>
        <p class="mt-3 text-lg text-white/85">Begin your child's journey at {{ $settings->school_name ?? 'our school' }}.</p>
        <nav class="mt-4 text-sm text-white/70">
            <a href="{{ $siteBase ?: '/' }}" class="hover:underline">Home</a>
            <span class="mx-2">/</span>
            <span>Admissions</span>
        </nav>
    </div>
</section>

{{-- Status banner --}}
<section class="py-12">
    <div class="max-w-5xl mx-auto px-6">
        @if ($settings->admission_open ?? false)
            <div class="rounded-2xl bg-emerald-50 border-2 border-emerald-200 p-8 text-center">
                <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    Admissions Open
                </div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900">We are accepting applications</h2>
                <p class="mt-2 text-gray-600 max-w-2xl mx-auto">Apply online today — fill the form, upload documents, and we'll get back to you with the next steps.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ $applyBase }}" class="bg-emerald-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition">
                        Apply Online →
                    </a>
                    @if ($settings->admission_form_url)
                        <a href="{{ $settings->admission_form_url }}" target="_blank"
                           class="border-2 border-emerald-600 text-emerald-700 px-6 py-3 rounded-lg font-semibold hover:bg-emerald-50">
                            Use External Form ↗
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-gray-100 p-8 text-center">
                <div class="inline-flex items-center gap-2 bg-gray-200 text-gray-700 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                    Admissions Closed
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Applications are not currently open</h2>
                <p class="mt-2 text-gray-600">Please check back later or contact us for the next admission cycle.</p>
                <a href="{{ $siteBase }}/contact" class="mt-4 inline-block bg-primary text-white px-6 py-2 rounded-lg font-semibold hover:opacity-90">Contact us</a>
            </div>
        @endif
    </div>
</section>

{{-- Admission process steps --}}
@php $steps = is_array($settings->admission_steps ?? null) ? $settings->admission_steps : []; @endphp
@if (! empty($steps))
    <section class="py-16 bg-white">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12">
                <div class="text-sm uppercase tracking-widest text-primary font-semibold">How it works</div>
                <h2 class="mt-1 text-3xl font-bold text-gray-900">The admission process</h2>
            </div>
            <ol class="relative space-y-6">
                @foreach ($steps as $i => $step)
                    <li class="flex gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg shadow-lg">
                            {{ $i + 1 }}
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg text-gray-900">{{ $step['title'] ?? '—' }}</h3>
                            <p class="mt-1 text-gray-600">{{ $step['description'] ?? '' }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endif

{{-- Why choose us re-iteration --}}
@php $reasons = is_array($settings->why_choose_us ?? null) ? array_slice($settings->why_choose_us, 0, 3) : []; @endphp
@if (! empty($reasons))
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Why choose {{ $settings->school_name ?? 'us' }}?</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($reasons as $r)
                    <div class="rounded-xl bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-lg text-gray-900">⭐ {{ $r['title'] ?? '—' }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ $r['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- CTA --}}
<section class="py-16 bg-primary text-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-2xl md:text-3xl font-bold">Have a question?</h2>
        <p class="mt-2 text-white/85">Reach out to our admissions office and we'll be happy to help.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ $siteBase }}/contact" class="bg-white text-primary px-6 py-3 rounded-lg font-semibold hover:bg-gray-100">Contact admissions</a>
            @if ($settings->admission_open ?? false)
                <a href="{{ $applyBase }}" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition">Apply Now</a>
            @endif
        </div>
    </div>
</section>
@endsection
