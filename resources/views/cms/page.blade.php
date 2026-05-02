@extends('cms.layout')

@section('title', ($page->meta_title ?? $page->title) . ' - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">{{ $page->title }}</h1>
            @if($page->meta_description)
                <p class="text-lg text-white/80">{{ $page->meta_description }}</p>
            @endif
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-primary">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">{{ $page->title }}</span>
            </nav>
        </div>
    </div>

    {{-- Page Content --}}
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4">
            <article class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                {!! $page->content !!}
            </article>

            @if($page->published_at)
                <div class="mt-12 pt-6 border-t text-sm text-gray-400">
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
