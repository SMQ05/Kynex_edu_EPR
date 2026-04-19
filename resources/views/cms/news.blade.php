@extends('cms.layout')

@section('title', 'News & Announcements - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">News & Announcements</h1>
            <p class="text-lg text-white/80">Stay updated with the latest from our school</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-primary">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">News</span>
            </nav>
        </div>
    </div>

    {{-- News Grid --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4">
            @if($announcements->count())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($announcements as $news)
                        <article class="bg-white border rounded-xl overflow-hidden hover:shadow-lg transition group">
                            <div class="bg-primary/5 px-6 py-4 border-b">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-primary font-semibold uppercase tracking-wide">
                                        {{ $news->published_at ? $news->published_at->format('M d, Y') : 'Announcement' }}
                                    </span>
                                    @if($news->expires_at)
                                        <span class="text-xs text-gray-400">
                                            Expires: {{ $news->expires_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-3 group-hover:text-primary transition">
                                    {{ $news->title }}
                                </h3>
                                <div class="text-gray-600 text-sm leading-relaxed">
                                    {!! Str::limit(strip_tags($news->content), 200) !!}
                                </div>
                                @if(strlen(strip_tags($news->content)) > 200)
                                    <button onclick="toggleContent(this)" 
                                            class="text-primary text-sm font-semibold mt-4 hover:underline"
                                            data-full="{!! e($news->content) !!}">
                                        Read More →
                                    </button>
                                @endif
                            </div>
                            <div class="px-6 pb-4">
                                @if($news->published_at)
                                    <span class="text-xs text-gray-400">
                                        {{ $news->published_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-10">
                    {{ $announcements->links() }}
                </div>
            @else
                <div class="text-center py-20">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    <h3 class="text-xl font-semibold text-gray-500 mb-2">No News Yet</h3>
                    <p class="text-gray-400">Announcements and news will appear here. Check back later!</p>
                </div>
            @endif
        </div>
    </section>

@endsection

{{-- News Detail Modal --}}
@push('scripts')
<script>
    function toggleContent(btn) {
        const article = btn.closest('article');
        const contentDiv = article.querySelector('.text-gray-600');
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
