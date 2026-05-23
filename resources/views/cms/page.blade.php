@extends('cms.layout')

@section('title', ($page->meta_title ?? $page->title) . ' - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <section class="relative grad-hero text-white py-20 sm:py-24 overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-3">{{ $page->title }}</h1>
            @if($page->meta_description)
                <p class="text-lg text-white/85 max-w-2xl mx-auto">{{ $page->meta_description }}</p>
            @endif
            <nav class="mt-5 text-sm text-white/70">
                <a href="{{ $siteBase ?: '/' }}" class="hover:text-white transition">Home</a>
                <span class="mx-2">/</span>
                <span class="text-white">{{ $page->title }}</span>
            </nav>
        </div>
    </section>

    {{-- Page Content --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-4xl mx-auto px-6">
            <article class="prose prose-lg max-w-none text-slate-700 leading-relaxed reveal">
                {!! $page->content !!}
            </article>

            @if($page->published_at)
                <div class="mt-12 pt-6 border-t border-slate-100 text-sm text-slate-400">
                    Last updated: {{ $page->published_at->format('F d, Y') }}
                </div>
            @endif
        </div>
    </section>

@endsection

@if($page->meta_description)
    @push('styles')
        <meta name="description" content="{{ $page->meta_description }}">
    @endpush
@endif
