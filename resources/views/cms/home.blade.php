@extends('cms.layout')

@section('title', ($settings->school_name ?? 'School') . ' - Welcome')

@section('content')

    {{-- Hero Slider --}}
    @if($sliders->count())
        <div id="hero-slider" class="relative overflow-hidden bg-gray-900" style="height: 520px;">
            @foreach($sliders as $index => $slider)
                <div class="slide absolute inset-0 transition-opacity duration-700 {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}"
                     data-index="{{ $index }}">
                    @if($slider->image_path)
                        <img src="{{ asset('storage/' . $slider->image_path) }}"
                             alt="{{ $slider->title }}"
                             class="w-full h-full object-cover">
                    @endif
                    <div class="absolute inset-0 bg-black/50"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-center px-4">
                        <div class="max-w-3xl">
                            @if($slider->title)
                                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 drop-shadow-lg">{{ $slider->title }}</h1>
                            @endif
                            @if($slider->subtitle)
                                <p class="text-lg md:text-xl text-gray-200 mb-6">{{ $slider->subtitle }}</p>
                            @endif
                            @if($slider->button_text && $slider->button_url)
                                <a href="{{ $slider->button_url }}"
                                   class="inline-block bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition text-lg">
                                    {{ $slider->button_text }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($sliders->count() > 1)
                <button onclick="changeSlide(-1)"
                        class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-3 rounded-full transition z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button onclick="changeSlide(1)"
                        class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-3 rounded-full transition z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                    @foreach($sliders as $index => $slider)
                        <button onclick="goToSlide({{ $index }})"
                                class="slider-dot w-3 h-3 rounded-full transition {{ $index === 0 ? 'bg-white' : 'bg-white/50' }}"
                                data-index="{{ $index }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- Default Hero when no sliders --}}
        <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-24">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $settings->school_name ?? 'Welcome to Our School' }}</h1>
                <p class="text-xl text-gray-200 mb-8">{{ $settings->tagline ?? 'Excellence in Education' }}</p>
                @if($settings->admission_open)
                    <a href="/admissions" class="inline-block bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg">
                        Apply for Admission
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Admission Banner --}}
    @if($settings->admission_open)
        <div class="bg-yellow-50 border-b border-yellow-200">
            <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-yellow-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                    </span>
                    <span class="font-semibold text-yellow-800">🎓 Admissions are now open!</span>
                </div>
                <a href="/admissions" class="bg-yellow-500 text-white px-4 py-1.5 rounded font-medium text-sm hover:bg-yellow-600 transition">
                    Learn More →
                </a>
            </div>
        </div>
    @endif

    {{-- Announcements Ticker --}}
    @if($announcements->count())
        <div class="bg-primary/5 border-b">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
                <span class="bg-primary text-white text-xs font-bold px-3 py-1 rounded shrink-0 uppercase tracking-wide">Latest</span>
                <div class="overflow-hidden flex-1">
                    <div class="announcement-ticker flex whitespace-nowrap">
                        @foreach($announcements as $announcement)
                            <span class="inline-block px-8 text-gray-700">
                                📢 {{ $announcement->title }}
                                @if($announcement->published_at)
                                    <span class="text-gray-400 text-sm ml-2">{{ $announcement->published_at->diffForHumans() }}</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- About Section --}}
    @if($settings->about_text)
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">About Our School</h2>
                        <div class="w-16 h-1 bg-primary mb-6"></div>
                        <div class="text-gray-600 leading-relaxed prose prose-sm max-w-none">
                            {!! Str::limit(strip_tags($settings->about_text), 500) !!}
                        </div>
                        <a href="/about" class="inline-block mt-6 text-primary font-semibold hover:underline">
                            Read More →
                        </a>
                    </div>
                    @if($settings->principal_photo_path || $settings->principal_name)
                        <div class="bg-gray-50 rounded-2xl p-8 text-center">
                            @if($settings->principal_photo_path)
                                <img src="{{ asset('storage/' . $settings->principal_photo_path) }}"
                                     alt="{{ $settings->principal_name }}"
                                     class="w-40 h-40 rounded-full mx-auto mb-4 object-cover shadow-lg border-4 border-white">
                            @else
                                <div class="w-40 h-40 rounded-full mx-auto mb-4 bg-primary/10 flex items-center justify-center">
                                    <svg class="w-20 h-20 text-primary/30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                            @endif
                            <h3 class="text-lg font-bold text-gray-900">{{ $settings->principal_name ?? 'Principal' }}</h3>
                            <p class="text-sm text-primary font-medium mb-4">Principal</p>
                            @if($settings->principal_message)
                                <blockquote class="text-gray-600 text-sm italic leading-relaxed">
                                    "{{ Str::limit(strip_tags($settings->principal_message), 250) }}"
                                </blockquote>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Quick Stats --}}
    <section class="bg-primary text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold mb-1">20+</div>
                    <div class="text-white/80 text-sm">Years of Excellence</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-1">1000+</div>
                    <div class="text-white/80 text-sm">Students Enrolled</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-1">50+</div>
                    <div class="text-white/80 text-sm">Qualified Teachers</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-1">95%</div>
                    <div class="text-white/80 text-sm">Pass Rate</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Latest News --}}
    @if($announcements->count())
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Latest News & Announcements</h2>
                    <div class="w-16 h-1 bg-primary mx-auto"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($announcements->take(3) as $news)
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden">
                            <div class="bg-primary/5 px-6 py-3 border-b">
                                <span class="text-xs text-primary font-semibold uppercase tracking-wide">
                                    {{ $news->published_at ? $news->published_at->format('M d, Y') : 'Announcement' }}
                                </span>
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $news->title }}</h3>
                                <p class="text-gray-600 text-sm leading-relaxed">{{ Str::limit(strip_tags($news->content), 120) }}</p>
                                <a href="/news" class="inline-block mt-4 text-primary text-sm font-semibold hover:underline">Read More →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-8">
                    <a href="/news" class="inline-block border-2 border-primary text-primary px-6 py-2 rounded-lg font-semibold hover:bg-primary hover:text-white transition">
                        View All News
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- CTA Section --}}
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to Join Our School?</h2>
            <p class="text-gray-600 mb-8 text-lg">Give your child the best education. Contact us today to learn more about our programs and admission process.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="/contact" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition text-lg">
                    Contact Us
                </a>
                @if($settings->admission_open)
                    <a href="/admissions" class="border-2 border-primary text-primary px-8 py-3 rounded-lg font-semibold hover:bg-primary hover:text-white transition text-lg">
                        Apply Now
                    </a>
                @endif
            </div>
        </div>
    </section>

@endsection

@push('styles')
<style>
    .announcement-ticker {
        animation: ticker 20s linear infinite;
    }
    .announcement-ticker:hover {
        animation-play-state: paused;
    }
    @keyframes ticker {
        0% { transform: translateX(100%); }
        100% { transform: translateX(-100%); }
    }
</style>
@endpush

@push('scripts')
<script>
    // Hero Slider
    (function() {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slider-dot');
        if (slides.length <= 1) return;

        let current = 0;
        let interval = setInterval(next, 5000);

        function next() { changeSlide(1); }

        window.changeSlide = function(dir) {
            slides[current].classList.replace('opacity-100', 'opacity-0');
            dots[current]?.classList.replace('bg-white', 'bg-white/50');
            current = (current + dir + slides.length) % slides.length;
            slides[current].classList.replace('opacity-0', 'opacity-100');
            dots[current]?.classList.replace('bg-white/50', 'bg-white');
            clearInterval(interval);
            interval = setInterval(next, 5000);
        };

        window.goToSlide = function(index) {
            slides[current].classList.replace('opacity-100', 'opacity-0');
            dots[current]?.classList.replace('bg-white', 'bg-white/50');
            current = index;
            slides[current].classList.replace('opacity-0', 'opacity-100');
            dots[current]?.classList.replace('bg-white/50', 'bg-white');
            clearInterval(interval);
            interval = setInterval(next, 5000);
        };
    })();
</script>
@endpush
