@extends('cms.layout')

@section('title', $settings->school_name ?? 'Welcome')

@section('content')
{{-- ── HERO ────────────────────────────────────────────────── --}}
<section class="relative overflow-hidden">
    @if ($sliders && $sliders->isNotEmpty())
        <div x-data="{ idx: 0, max: {{ $sliders->count() }} }"
             x-init="setInterval(() => idx = (idx + 1) % max, 6000)"
             class="relative h-[480px] md:h-[600px]">
            @foreach ($sliders as $i => $slide)
                <div x-show="idx === {{ $i }}" x-transition.opacity.duration.700ms
                     class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('storage/' . $slide->image_path) }}');">
                    <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
                    <div class="relative h-full max-w-7xl mx-auto px-6 flex flex-col justify-center text-white">
                        <h1 class="text-3xl md:text-5xl font-bold leading-tight max-w-2xl">{{ $slide->title }}</h1>
                        @if ($slide->subtitle)
                            <p class="mt-4 text-lg md:text-xl text-white/90 max-w-2xl">{{ $slide->subtitle }}</p>
                        @endif
                        @if ($slide->button_text)
                            <a href="{{ $slide->button_url ?: $applyBase }}"
                               class="mt-6 inline-block bg-white text-primary font-semibold px-7 py-3 rounded-lg hover:bg-gray-100 transition w-fit">
                                {{ $slide->button_text }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
            @if ($sliders->count() > 1)
                <div class="absolute bottom-6 left-0 right-0 flex justify-center gap-2 z-10">
                    @foreach ($sliders as $i => $_)
                        <button type="button" @click="idx = {{ $i }}"
                                class="h-2 rounded-full transition-all"
                                :class="idx === {{ $i }} ? 'w-8 bg-white' : 'w-2 bg-white/50'"></button>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="h-[420px] md:h-[520px] bg-cover bg-center"
             @if ($settings->hero_image_path)
                style="background-image: url('{{ asset('storage/' . $settings->hero_image_path) }}');"
             @else
                style="background: linear-gradient(135deg, var(--primary, #1e3a8a) 0%, #0f172a 100%);"
             @endif
        >
            <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
            <div class="relative h-full max-w-7xl mx-auto px-6 flex flex-col justify-center text-white">
                <h1 class="text-3xl md:text-5xl font-bold leading-tight max-w-2xl">{{ $settings->school_name ?? 'Welcome to our school' }}</h1>
                <p class="mt-4 text-lg md:text-xl text-white/90 max-w-2xl">{{ $settings->tagline ?? 'Excellence in education and character building' }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($settings->admission_open ?? false)
                        <a href="{{ $applyBase }}" class="bg-white text-primary font-semibold px-7 py-3 rounded-lg hover:bg-gray-100 transition">Apply for Admission</a>
                    @endif
                    <a href="{{ $siteBase }}/about" class="bg-transparent border-2 border-white text-white font-semibold px-7 py-3 rounded-lg hover:bg-white hover:text-primary transition">Learn More</a>
                </div>
            </div>
        </div>
    @endif
</section>

{{-- ── ADMISSION RIBBON ─────────────────────────────────────── --}}
@if ($settings->admission_open ?? false)
    <section class="bg-yellow-400 text-yellow-900">
        <div class="max-w-7xl mx-auto px-6 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="font-medium">📣 Admissions are <strong>open</strong> — applications are being accepted now.</div>
            <a href="{{ $applyBase }}" class="bg-yellow-900 text-white px-5 py-1.5 rounded font-semibold text-sm hover:bg-yellow-950 transition">Apply Online →</a>
        </div>
    </section>
@endif

{{-- ── STATS ───────────────────────────────────────────────── --}}
@php $stats = is_array($settings->stats ?? null) ? $settings->stats : []; @endphp
@if (! empty($stats))
    <section class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-{{ min(4, count($stats)) }} gap-6 text-center">
                @foreach ($stats as $stat)
                    <div class="rounded-xl bg-gray-50 p-6 hover:shadow-lg transition">
                        <div class="text-3xl md:text-4xl font-bold text-primary">{{ $stat['value'] ?? '—' }}</div>
                        <div class="text-sm text-gray-600 mt-1">{{ $stat['label'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── WHY CHOOSE US ───────────────────────────────────────── --}}
@php $reasons = is_array($settings->why_choose_us ?? null) ? $settings->why_choose_us : []; @endphp
@if (! empty($reasons))
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Why choose {{ $settings->school_name ?? 'us' }}?</h2>
                <div class="mt-3 mx-auto h-1 w-16 bg-primary rounded"></div>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($reasons as $r)
                    <div class="rounded-xl bg-white p-6 shadow-sm hover:shadow-xl transition border border-gray-100">
                        <div class="w-12 h-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center mb-4 text-2xl">
                            ⭐
                        </div>
                        <h3 class="font-semibold text-lg text-gray-900">{{ $r['title'] ?? '—' }}</h3>
                        <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ $r['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── ABOUT + PRINCIPAL ───────────────────────────────────── --}}
@if (filled($settings->about_text) || filled($settings->principal_message))
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div>
                @if ($settings->about_image_path)
                    <img src="{{ asset('storage/' . $settings->about_image_path) }}" alt="About" class="rounded-xl w-full h-80 object-cover shadow-lg">
                @elseif ($settings->logo_path)
                    <div class="bg-gray-100 rounded-xl h-80 flex items-center justify-center">
                        <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo" class="max-h-48">
                    </div>
                @endif
            </div>
            <div>
                <div class="text-sm uppercase tracking-widest text-primary font-semibold">About us</div>
                <h2 class="mt-2 text-3xl font-bold text-gray-900">{{ $settings->school_name ?? 'Our School' }}</h2>
                <div class="mt-4 prose prose-sm text-gray-600 max-w-none">{!! $settings->about_text ?? '' !!}</div>
                <a href="{{ $siteBase }}/about" class="mt-6 inline-block text-primary font-semibold hover:underline">Read more →</a>
            </div>
        </div>
    </section>
@endif

{{-- ── PRINCIPAL'S MESSAGE ─────────────────────────────────── --}}
@if (filled($settings->principal_message))
    <section class="py-16 bg-gray-50">
        <div class="max-w-5xl mx-auto px-6">
            <div class="rounded-2xl bg-white p-8 md:p-12 shadow-md grid md:grid-cols-3 gap-8">
                <div class="md:col-span-1 text-center">
                    @if ($settings->principal_photo_path)
                        <img src="{{ asset('storage/' . $settings->principal_photo_path) }}" alt="Principal"
                             class="w-40 h-40 rounded-full object-cover mx-auto shadow-lg">
                    @else
                        <div class="w-40 h-40 rounded-full bg-primary/10 mx-auto"></div>
                    @endif
                    <div class="mt-3 font-semibold text-gray-900">{{ $settings->principal_name ?? 'Principal' }}</div>
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Principal's Message</div>
                </div>
                <div class="md:col-span-2 prose prose-sm text-gray-700 max-w-none">
                    <span class="text-primary text-4xl leading-none">"</span>
                    {!! $settings->principal_message !!}
                </div>
            </div>
        </div>
    </section>
@endif

{{-- ── FACILITIES ──────────────────────────────────────────── --}}
@php $facilities = is_array($settings->facilities ?? null) ? $settings->facilities : []; @endphp
@if (! empty($facilities))
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Our facilities</h2>
                <div class="mt-3 mx-auto h-1 w-16 bg-primary rounded"></div>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($facilities as $f)
                    <div class="rounded-xl overflow-hidden border border-gray-100 bg-white shadow-sm hover:shadow-xl transition">
                        @if (! empty($f['image_path']))
                            <img src="{{ asset('storage/' . $f['image_path']) }}" alt="{{ $f['name'] ?? '' }}" class="w-full h-44 object-cover">
                        @else
                            <div class="h-44 bg-gradient-to-br from-primary/20 to-primary/5"></div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-semibold text-gray-900">{{ $f['name'] ?? '—' }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $f['description'] ?? '' }}</p>
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
    <section class="py-12 bg-primary text-white">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-2xl font-bold text-center">Recent results</h2>
            <div class="mt-8 grid md:grid-cols-{{ min(3, count($exams)) }} gap-4">
                @foreach ($exams as $e)
                    <div class="rounded-xl bg-white/10 backdrop-blur p-6 text-center">
                        <div class="text-sm uppercase tracking-widest text-white/70">{{ $e['exam'] ?? '' }}</div>
                        <div class="mt-2 text-2xl font-bold">{{ $e['result'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── TESTIMONIALS ─────────────────────────────────────────── --}}
@php $testimonials = is_array($settings->testimonials ?? null) ? $settings->testimonials : []; @endphp
@if (! empty($testimonials))
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">What people say</h2>
                <div class="mt-3 mx-auto h-1 w-16 bg-primary rounded"></div>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($testimonials as $t)
                    <div class="rounded-xl bg-white p-6 shadow-sm hover:shadow-xl transition">
                        <div class="text-primary text-4xl leading-none">"</div>
                        <p class="mt-1 text-gray-700 leading-relaxed">{{ $t['quote'] ?? '' }}</p>
                        <div class="mt-4 flex items-center gap-3">
                            @if (! empty($t['photo_path']))
                                <img src="{{ asset('storage/' . $t['photo_path']) }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                                <div class="w-10 h-10 rounded-full bg-primary/10"></div>
                            @endif
                            <div>
                                <div class="font-semibold text-gray-900 text-sm">{{ $t['author'] ?? '' }}</div>
                                <div class="text-xs text-gray-500">{{ $t['role'] ?? '' }}</div>
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
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">Latest news</h2>
                    <div class="mt-3 h-1 w-16 bg-primary rounded"></div>
                </div>
                <a href="{{ $siteBase }}/news" class="text-primary font-semibold hover:underline">View all →</a>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($announcements as $a)
                    <article class="rounded-xl border border-gray-100 bg-white p-6 hover:shadow-lg transition">
                        <div class="text-xs text-primary font-semibold">{{ optional($a->published_at)->format('d M Y') ?: optional($a->created_at)->format('d M Y') }}</div>
                        <h3 class="mt-2 text-lg font-semibold text-gray-900 line-clamp-2">{{ $a->title }}</h3>
                        <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ \Illuminate\Support\Str::limit(strip_tags($a->content), 120) }}</p>
                        <a href="{{ $siteBase }}/news" class="mt-3 inline-block text-primary text-sm font-semibold">Read more →</a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- ── CTA ─────────────────────────────────────────────────── --}}
<section class="py-16 bg-gradient-to-r from-primary to-blue-700 text-white">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <h2 class="text-3xl md:text-4xl font-bold">Ready to join us?</h2>
        <p class="mt-3 text-white/90 text-lg">Get in touch to learn more about our programs, or apply online today.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            @if ($settings->admission_open ?? false)
                <a href="{{ $applyBase }}" class="bg-white text-primary font-semibold px-7 py-3 rounded-lg hover:bg-gray-100 transition">Apply Online</a>
            @endif
            <a href="{{ $siteBase }}/contact" class="border-2 border-white text-white font-semibold px-7 py-3 rounded-lg hover:bg-white hover:text-primary transition">Contact Us</a>
        </div>
    </div>
</section>
@endsection
