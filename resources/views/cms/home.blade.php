@extends('cms.layout')

@section('title', $settings->school_name ?? 'Welcome')

@section('content')
{{-- ── HERO ────────────────────────────────────────────────── --}}
<section class="relative overflow-hidden">
    @if ($sliders && $sliders->isNotEmpty())
        <div x-data="{ idx: 0, max: {{ $sliders->count() }} }"
             x-init="setInterval(() => idx = (idx + 1) % max, 6000)"
             class="relative h-[520px] md:h-[640px]">
            @foreach ($sliders as $i => $slide)
                <div x-show="idx === {{ $i }}" x-transition.opacity.duration.700ms
                     class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('storage/' . $slide->image_path) }}');">
                    <div class="absolute inset-0" style="background:linear-gradient(115deg, rgba(30,27,75,.88) 0%, rgba(30,58,138,.62) 45%, rgba(14,165,233,.30) 100%);"></div>
                    <div class="relative h-full max-w-7xl mx-auto px-6 flex flex-col justify-center text-white">
                        <div class="max-w-2xl">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/25 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-semibold mb-5">
                                <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
                                {{ $settings->school_name ?? 'Welcome' }}
                            </span>
                            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-[1.1]">{{ $slide->title }}</h1>
                            @if ($slide->subtitle)
                                <p class="mt-5 text-lg md:text-xl text-white/90">{{ $slide->subtitle }}</p>
                            @endif
                            <div class="mt-7 flex flex-wrap gap-3">
                                @if ($slide->button_text)
                                    <a href="{{ $slide->button_url ?: $applyBase }}"
                                       class="inline-flex items-center justify-center min-h-[48px] bg-white text-primary font-semibold px-7 py-3 rounded-xl shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition">
                                        {{ $slide->button_text }}
                                    </a>
                                @else
                                    <a href="{{ $applyBase }}"
                                       class="inline-flex items-center justify-center min-h-[48px] bg-white text-primary font-semibold px-7 py-3 rounded-xl shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition">
                                        Apply for Admission →
                                    </a>
                                @endif
                                <a href="{{ $siteBase }}/about"
                                   class="inline-flex items-center justify-center min-h-[48px] bg-white/10 border border-white/40 backdrop-blur text-white font-semibold px-7 py-3 rounded-xl hover:bg-white/20 transition">
                                    Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            @if ($sliders->count() > 1)
                <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-2 z-10">
                    @foreach ($sliders as $i => $_)
                        <button type="button" @click="idx = {{ $i }}" aria-label="Go to slide {{ $i + 1 }}"
                                class="h-2.5 rounded-full transition-all"
                                :class="idx === {{ $i }} ? 'w-8 bg-white' : 'w-2.5 bg-white/50'"></button>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="relative grad-hero h-[480px] md:h-[560px] bg-cover bg-center"
             @if ($settings->hero_image_path)
                style="background-image: url('{{ asset('storage/' . $settings->hero_image_path) }}');"
             @endif
        >
            <div class="absolute inset-0" style="background:linear-gradient(115deg, rgba(30,27,75,.90) 0%, rgba(30,58,138,.66) 45%, rgba(14,165,233,.34) 100%);"></div>
            {{-- decorative glow --}}
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="relative h-full max-w-7xl mx-auto px-6 flex flex-col justify-center text-white">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/25 backdrop-blur px-4 py-1.5 text-xs sm:text-sm font-semibold mb-5">
                        <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        Welcome
                    </span>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-[1.1]">{{ $settings->school_name ?? 'Welcome to our school' }}</h1>
                    <p class="mt-5 text-lg md:text-xl text-white/90">{{ $settings->tagline ?? 'Excellence in education and character building' }}</p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        @if ($settings->admission_open ?? false)
                            <a href="{{ $applyBase }}" class="inline-flex items-center justify-center min-h-[48px] bg-white text-primary font-semibold px-7 py-3 rounded-xl shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition">Apply for Admission →</a>
                        @endif
                        <a href="{{ $siteBase }}/about" class="inline-flex items-center justify-center min-h-[48px] bg-white/10 border border-white/40 backdrop-blur text-white font-semibold px-7 py-3 rounded-xl hover:bg-white/20 transition">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>

{{-- ── ADMISSION RIBBON ─────────────────────────────────────── --}}
@if ($settings->admission_open ?? false)
    <section class="bg-amber-400 text-amber-950">
        <div class="max-w-7xl mx-auto px-6 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="font-semibold flex items-center gap-2">
                <span class="text-lg">📣</span> Admissions are <strong>open</strong> — applications are being accepted now.
            </div>
            <a href="{{ $applyBase }}" class="inline-flex items-center min-h-[44px] bg-amber-950 text-white px-5 py-2 rounded-xl font-semibold text-sm hover:bg-black transition">Apply Online →</a>
        </div>
    </section>
@endif

{{-- ── STATS ───────────────────────────────────────────────── --}}
@php $stats = is_array($settings->stats ?? null) ? $settings->stats : []; @endphp
@if (! empty($stats))
    <section class="bg-white py-14 sm:py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-{{ min(4, count($stats)) }} gap-4 sm:gap-6 text-center">
                @foreach ($stats as $stat)
                    <div class="reveal rounded-2xl bg-slate-50 border border-slate-100 p-6 hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="text-3xl md:text-4xl font-extrabold tracking-tight grad-text">{{ $stat['value'] ?? '—' }}</div>
                        <div class="text-sm text-slate-500 mt-2 font-medium">{{ $stat['label'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── WHY CHOOSE US ───────────────────────────────────────── --}}
@php $reasons = is_array($settings->why_choose_us ?? null) ? $settings->why_choose_us : []; @endphp
@if (! empty($reasons))
    <section class="py-16 sm:py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">Why us</span>
                <h2 class="mt-4 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900">Why choose {{ $settings->school_name ?? 'us' }}?</h2>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($reasons as $r)
                    <div class="reveal rounded-2xl bg-white p-7 shadow-sm hover:shadow-xl hover:-translate-y-1 transition border border-slate-100">
                        <div class="w-12 h-12 rounded-xl grad-brand text-white flex items-center justify-center mb-5 text-2xl shadow-md">⭐</div>
                        <h3 class="font-bold text-lg text-slate-900">{{ $r['title'] ?? '—' }}</h3>
                        <p class="mt-2 text-sm text-slate-500 leading-relaxed">{{ $r['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── ABOUT + PRINCIPAL ───────────────────────────────────── --}}
@if (filled($settings->about_text) || filled($settings->principal_message))
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div class="reveal">
                @if ($settings->about_image_path)
                    <img src="{{ asset('storage/' . $settings->about_image_path) }}" alt="About" class="rounded-3xl w-full h-80 object-cover shadow-xl">
                @elseif ($settings->logo_path)
                    <div class="bg-slate-50 border border-slate-100 rounded-3xl h-80 flex items-center justify-center">
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo" class="max-h-48">
                    </div>
                @else
                    <div class="rounded-3xl h-80 grad-brand opacity-90"></div>
                @endif
            </div>
            <div class="reveal">
                <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">About us</span>
                <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">{{ $settings->school_name ?? 'Our School' }}</h2>
                <div class="mt-5 prose prose-sm text-slate-600 max-w-none">{!! $settings->about_text ?? '' !!}</div>
                <a href="{{ $siteBase }}/about" class="mt-6 inline-flex items-center gap-1 text-primary font-semibold hover:gap-2 transition-all">Read more →</a>
            </div>
        </div>
    </section>
@endif

{{-- ── PRINCIPAL'S MESSAGE ─────────────────────────────────── --}}
@if (filled($settings->principal_message))
    <section class="py-16 sm:py-20 bg-slate-50">
        <div class="max-w-5xl mx-auto px-6">
            <div class="reveal rounded-3xl bg-white p-8 md:p-12 shadow-lg border border-slate-100 grid md:grid-cols-3 gap-8">
                <div class="md:col-span-1 text-center">
                    @if ($settings->principal_photo_path)
                        <img src="{{ asset('storage/' . $settings->principal_photo_path) }}" alt="Principal"
                             class="w-40 h-40 rounded-full object-cover mx-auto shadow-lg ring-4 ring-white">
                    @else
                        <div class="w-40 h-40 rounded-full grad-brand mx-auto opacity-90"></div>
                    @endif
                    <div class="mt-4 font-bold text-slate-900">{{ $settings->principal_name ?? 'Principal' }}</div>
                    <div class="text-xs text-primary uppercase tracking-widest font-semibold">Principal's Message</div>
                </div>
                <div class="md:col-span-2 prose prose-sm text-slate-700 max-w-none">
                    <span class="grad-text text-5xl leading-none font-serif">"</span>
                    {!! $settings->principal_message !!}
                </div>
            </div>
        </div>
    </section>
@endif

{{-- ── FACILITIES ──────────────────────────────────────────── --}}
@php $facilities = is_array($settings->facilities ?? null) ? $settings->facilities : []; @endphp
@if (! empty($facilities))
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">Campus</span>
                <h2 class="mt-4 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900">Our facilities</h2>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($facilities as $f)
                    <div class="reveal rounded-2xl overflow-hidden border border-slate-100 bg-white shadow-sm hover:shadow-xl hover:-translate-y-1 transition group">
                        @if (! empty($f['image_path']))
                            <div class="h-44 overflow-hidden">
                                <img src="{{ asset('storage/' . $f['image_path']) }}" alt="{{ $f['name'] ?? '' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                            </div>
                        @else
                            <div class="h-44 grad-brand opacity-90"></div>
                        @endif
                        <div class="p-6">
                            <h3 class="font-bold text-slate-900">{{ $f['name'] ?? '—' }}</h3>
                            <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">{{ $f['description'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── EXAM HIGHLIGHTS ──────────────────────────────────────── --}}
@php $exams = is_array($settings->exam_highlights ?? null) ? $settings->exam_highlights : []; @endphp
@if (! empty($exams))
    <section class="py-16 sm:py-20 grad-hero text-white relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-6">
            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-center reveal">Recent results</h2>
            <div class="mt-10 grid md:grid-cols-{{ min(3, count($exams)) }} gap-5">
                @foreach ($exams as $e)
                    <div class="reveal rounded-2xl bg-white/10 backdrop-blur border border-white/15 p-7 text-center">
                        <div class="text-sm uppercase tracking-widest text-white/70 font-semibold">{{ $e['exam'] ?? '' }}</div>
                        <div class="mt-3 text-3xl font-extrabold">{{ $e['result'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── TESTIMONIALS ─────────────────────────────────────────── --}}
@php $testimonials = is_array($settings->testimonials ?? null) ? $settings->testimonials : []; @endphp
@if (! empty($testimonials))
    <section class="py-16 sm:py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12 reveal">
                <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">Voices</span>
                <h2 class="mt-4 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900">What people say</h2>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($testimonials as $t)
                    <div class="reveal rounded-2xl bg-white p-7 shadow-sm hover:shadow-xl hover:-translate-y-1 transition border border-slate-100">
                        <div class="grad-text text-5xl leading-none font-serif">"</div>
                        <p class="mt-1 text-slate-700 leading-relaxed">{{ $t['quote'] ?? '' }}</p>
                        <div class="mt-5 flex items-center gap-3">
                            @if (! empty($t['photo_path']))
                                <img src="{{ asset('storage/' . $t['photo_path']) }}" class="w-11 h-11 rounded-full object-cover" alt="{{ $t['author'] ?? '' }}">
                            @else
                                <div class="w-11 h-11 rounded-full grad-brand opacity-90"></div>
                            @endif
                            <div>
                                <div class="font-bold text-slate-900 text-sm">{{ $t['author'] ?? '' }}</div>
                                <div class="text-xs text-slate-500">{{ $t['role'] ?? '' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── LATEST ANNOUNCEMENTS ────────────────────────────────── --}}
@if ($announcements && $announcements->isNotEmpty())
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-10 reveal">
                <div>
                    <span class="inline-flex items-center rounded-full bg-primary/10 text-primary px-4 py-1.5 text-xs font-bold uppercase tracking-widest">Updates</span>
                    <h2 class="mt-4 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900">Latest news</h2>
                </div>
                <a href="{{ $siteBase }}/news" class="inline-flex items-center gap-1 text-primary font-semibold hover:gap-2 transition-all">View all →</a>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($announcements as $a)
                    <article class="reveal rounded-2xl border border-slate-100 bg-white p-7 shadow-sm hover:shadow-xl hover:-translate-y-1 transition">
                        <div class="inline-flex items-center text-xs text-primary font-bold uppercase tracking-wide">{{ optional($a->published_at)->format('d M Y') ?: optional($a->created_at)->format('d M Y') }}</div>
                        <h3 class="mt-3 text-lg font-bold text-slate-900 line-clamp-2">{{ $a->title }}</h3>
                        <p class="mt-2 text-sm text-slate-500 leading-relaxed line-clamp-3">{{ \Illuminate\Support\Str::limit(strip_tags($a->content), 120) }}</p>
                        <a href="{{ $siteBase }}/news" class="mt-4 inline-flex items-center gap-1 text-primary text-sm font-semibold hover:gap-2 transition-all">Read more →</a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── CTA BAND ────────────────────────────────────────────── --}}
<section class="py-16 sm:py-20 grad-hero text-white relative overflow-hidden">
    <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
    <div class="absolute -top-24 -left-24 w-80 h-80 rounded-full bg-indigo-500/20 blur-3xl"></div>
    <div class="relative max-w-4xl mx-auto px-6 text-center reveal">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">Ready to join us?</h2>
        <p class="mt-4 text-white/85 text-lg">Get in touch to learn more about our programs, or apply online today.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            @if ($settings->admission_open ?? false)
                <a href="{{ $applyBase }}" class="inline-flex items-center justify-center min-h-[48px] bg-white text-primary font-semibold px-8 py-3 rounded-xl shadow-lg hover:-translate-y-0.5 hover:shadow-xl transition">Apply Online</a>
            @endif
            <a href="{{ $siteBase }}/contact" class="inline-flex items-center justify-center min-h-[48px] bg-white/10 border border-white/40 backdrop-blur text-white font-semibold px-8 py-3 rounded-xl hover:bg-white/20 transition">Contact Us</a>
        </div>
    </div>
</section>
@endsection
