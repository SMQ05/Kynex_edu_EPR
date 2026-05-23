@extends('cms.layout')

@section('title', 'Gallery - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <section class="relative grad-hero text-white py-20 sm:py-24 overflow-hidden">
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-3">Photo Gallery</h1>
            <p class="text-lg text-white/85">Memories and moments from our school life</p>
            <nav class="mt-5 text-sm text-white/70">
                <a href="{{ $siteBase ?: '/' }}" class="hover:text-white transition">Home</a>
                <span class="mx-2">/</span>
                <span class="text-white">Gallery</span>
            </nav>
        </div>
    </section>

    {{-- Albums --}}
    <section class="py-16 sm:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            @if($albums->count())
                @foreach($albums as $album)
                    <div class="mb-16 last:mb-0 reveal">
                        <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
                            <div>
                                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">{{ $album->title }}</h2>
                                @if($album->description)
                                    <p class="text-slate-500 mt-1.5">{{ $album->description }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-500 px-3 py-1 text-sm font-medium">{{ $album->photos->count() }} photos</span>
                        </div>

                        @if($album->photos->count())
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                                @foreach($album->photos as $photo)
                                    <button type="button" class="group block text-left"
                                         onclick="openLightbox('{{ asset('storage/' . $photo->image_path) }}', '{{ addslashes($photo->title ?? $album->title) }}')">
                                        <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 shadow-sm group-hover:shadow-xl transition">
                                            <img src="{{ asset('storage/' . $photo->image_path) }}"
                                                 alt="{{ $photo->title ?? $album->title }}"
                                                 class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                                                 loading="lazy">
                                        </div>
                                        @if($photo->title)
                                            <p class="text-xs text-slate-500 mt-2 truncate">{{ $photo->title }}</p>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="text-slate-400 text-sm italic">No photos in this album yet.</p>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-20">
                    <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-600 mb-2">No Albums Yet</h3>
                    <p class="text-slate-400">Photo albums will be added soon. Check back later!</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Lightbox Modal --}}
    <div id="lightbox" class="fixed inset-0 bg-black/90 backdrop-blur-sm z-[80] hidden items-center justify-center" onclick="closeLightbox()">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center z-10 transition" aria-label="Close">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="max-w-5xl max-h-[90vh] p-4" onclick="event.stopPropagation()">
            <img id="lightbox-img" src="" alt="" class="max-w-full max-h-[80vh] mx-auto rounded-2xl shadow-2xl">
            <p id="lightbox-caption" class="text-white text-center mt-4 text-sm"></p>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function openLightbox(src, caption) {
        document.getElementById('lightbox-img').src = src;
        document.getElementById('lightbox-caption').textContent = caption;
        const lb = document.getElementById('lightbox');
        lb.classList.remove('hidden');
        lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        const lb = document.getElementById('lightbox');
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLightbox();
    });
</script>
@endpush
