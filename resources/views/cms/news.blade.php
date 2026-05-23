@extends('cms.layout')

@section('title', 'News & Announcements - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <section class="relative grad-hero text-white py-20 sm:py-24 overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-3">News &amp; Announcements</h1>
            <p class="text-lg text-white/85">Stay updated with the latest from our school</p>
            <nav class="mt-5 text-sm text-white/70">
                <a href="{{ $siteBase ?: '/' }}" class="hover:text-white transition">Home</a>
                <span class="mx-2">/</span>
                <span class="text-white">News</span>
            </nav>
        </div>
    </section>

    {{-- News Grid --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            @if($announcements->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    @foreach($announcements as $news)
                        <article class="reveal bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition group flex flex-col">
                            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs text-primary font-bold uppercase tracking-wide">
                                        {{ $news->published_at ? $news->published_at->format('M d, Y') : 'Announcement' }}
                                    </span>
                                    @if($news->expires_at)
                                        <span class="text-xs text-slate-400">
                                            Expires: {{ $news->expires_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="p-6 flex-1">
                                <h3 class="text-lg font-bold text-slate-900 mb-3 group-hover:text-primary transition">
                                    {{ $news->title }}
                                </h3>
                                <div class="news-content text-slate-600 text-sm leading-relaxed">
                                    {!! Str::limit(strip_tags($news->content), 200) !!}
                                </div>
                                @if(strlen(strip_tags($news->content)) > 200)
                                    <button onclick="toggleContent(this)"
                                            class="text-primary text-sm font-semibold mt-4 inline-flex items-center gap-1 hover:gap-2 transition-all"
                                            data-full="{!! e($news->content) !!}">
                                        Read More →
                                    </button>
                                @endif
                            </div>
                            @if($news->published_at)
                                <div class="px-6 pb-4">
                                    <span class="text-xs text-slate-400">
                                        {{ $news->published_at->diffForHumans() }}
                                    </span>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-12">
                    {{ $announcements->links() }}
                </div>
            @else
                <div class="text-center py-20">
                    <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-600 mb-2">No News Yet</h3>
                    <p class="text-slate-400">Announcements and news will appear here. Check back later!</p>
                </div>
            @endif
        </div>
    </section>

@endsection

{{-- News Detail toggle --}}
@push('scripts')
<script>
    function toggleContent(btn) {
        const article = btn.closest('article');
        const contentDiv = article.querySelector('.news-content');
        const fullContent = btn.dataset.full;

        if (btn.textContent.trim() === 'Read More →') {
            contentDiv.innerHTML = fullContent;
            btn.textContent = '← Read Less';
        } else {
            contentDiv.innerHTML = contentDiv.textContent.substring(0, 200) + '...';
            btn.textContent = 'Read More →';
        }
    }
</script>
@endpush
