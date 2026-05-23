@extends('cms.layout')

@section('title', 'Admissions — ' . ($settings->school_name ?? 'School'))

@section('content')
{{-- Hero --}}
<section class="relative grad-hero text-white py-20 sm:py-24 overflow-hidden">
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-6 text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">Admissions</h1>
        <p class="mt-4 text-lg text-white/85 max-w-2xl mx-auto">Begin your child's journey at {{ $settings->school_name ?? 'our school' }}.</p>
        <nav class="mt-5 text-sm text-white/70">
            <a href="{{ $siteBase ?: '/' }}" class="hover:text-white transition">Home</a>
            <span class="mx-2">/</span>
            <span class="text-white">Admissions</span>
        </nav>
    </div>
</section>

{{-- Status banner --}}
<section class="py-14 sm:py-16">
    <div class="max-w-5xl mx-auto px-6">
        @if ($settings->admission_open ?? false)
            <div class="reveal rounded-3xl bg-emerald-50 border-2 border-emerald-200 p-8 sm:p-12 text-center shadow-sm">
                <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 px-4 py-2 rounded-full text-sm font-bold mb-5">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    Admissions Open
                </div>
                <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">We are accepting applications</h2>
                <p class="mt-3 text-slate-600 max-w-2xl mx-auto text-lg">Apply online today — fill the form, upload documents, and we'll get back to you with the next steps.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ $applyBase }}" class="inline-flex items-center justify-center min-h-[52px] grad-brand text-white px-10 py-3.5 rounded-xl font-bold text-lg shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition">
                        Apply Online →
                    </a>
                    @if ($settings->admission_form_url)
                        <a href="{{ $settings->admission_form_url }}" target="_blank" rel="noopener"
                           class="inline-flex items-center justify-center min-h-[52px] border-2 border-emerald-600 text-emerald-700 px-7 py-3 rounded-xl font-semibold hover:bg-emerald-50 transition">
                            Use External Form ↗
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="reveal rounded-3xl bg-slate-100 border border-slate-200 p-8 sm:p-12 text-center">
                <div class="inline-flex items-center gap-2 bg-slate-200 text-slate-700 px-4 py-2 rounded-full text-sm font-bold mb-5">
                    Admissions Closed
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Applications are not currently open</h2>
                <p class="mt-3 text-slate-600 text-lg">Please check back later or contact us for the next admission cycle.</p>
                <a href="{{ $siteBase }}/contact" class="mt-6 inline-flex items-center justify-center min-h-[48px] grad-brand text-white px-7 py-3 rounded-xl font-semibold shadow-md hover:-translate-y-0.5 hover:shadow-xl transition">Contact us</a>
            </div>
        @endif
    </div>
</section>

{{-- Admission process steps --}}
@php $steps = is_array($settings->admission_steps ?? null) ? $settings->admission_steps : []; @endphp
@if (! empty($steps))
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">How it works</span>
                <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">The admission process</h2>
            </div>
            <ol class="relative space-y-5">
                @foreach ($steps as $i => $step)
                    <li class="reveal flex gap-5 rounded-2xl bg-slate-50 border border-slate-100 p-6 hover:shadow-lg transition">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center font-extrabold text-lg shadow-md">
                            {{ $i + 1 }}
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-lg text-slate-900">{{ $step['title'] ?? '—' }}</h3>
                            <p class="mt-1 text-slate-600 leading-relaxed">{{ $step['description'] ?? '' }}</p>
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
    <section class="py-16 sm:py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">Why us</span>
                <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Why choose {{ $settings->school_name ?? 'us' }}?</h2>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($reasons as $r)
                    <div class="reveal rounded-2xl bg-white p-7 shadow-sm hover:shadow-xl hover:-translate-y-1 transition border border-slate-100">
                        <div class="w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center mb-4 text-2xl shadow-md">⭐</div>
                        <h3 class="font-bold text-lg text-slate-900">{{ $r['title'] ?? '—' }}</h3>
                        <p class="mt-2 text-sm text-slate-500 leading-relaxed">{{ $r['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- CTA --}}
<section class="py-16 sm:py-20 grad-hero text-white relative overflow-hidden">
    <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
    <div class="relative max-w-4xl mx-auto px-6 text-center reveal">
        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Have a question?</h2>
        <p class="mt-3 text-white/85 text-lg">Reach out to our admissions office and we'll be happy to help.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="{{ $siteBase }}/contact" class="inline-flex items-center justify-center min-h-[48px] bg-white text-primary px-7 py-3 rounded-xl font-semibold shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition">Contact admissions</a>
            @if ($settings->admission_open ?? false)
                <a href="{{ $applyBase }}" class="inline-flex items-center justify-center min-h-[48px] bg-white/10 border border-white/40 backdrop-blur text-white px-7 py-3 rounded-xl font-semibold hover:bg-white/20 transition">Apply Now</a>
            @endif
        </div>
    </div>
</section>
@endsection
