@extends('cms.layout')

@section('title', 'Gallery - ' . ($settings->school_name ?? 'School'))

@section('content')

    {{-- Page Header --}}
    <div class="bg-gradient-to-r from-primary to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-2">Photo Gallery</h1>
            <p class="text-lg text-white/80">Memories and moments from our school life</p>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="bg-gray-50 border-b">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <nav class="text-sm text-gray-500">
                <a href="/" class="hover:text-primary">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">Gallery</span>
            </nav>
        </div>
    </div>

    {{-- Albums --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4">
            @if($albums->count())
                @foreach($albums as $album)
                    <div class="mb-16">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ $album->title }}</h2>
                                @if($album->description)
                                    <p class="text-gray-600 mt-1">{{ $album->description }}</p>
                                @endif
                            </div>
                            <span class="text-sm text-gray-400">{{ $album->photos->count() }} photos</span>
                        </div>

                        @if($album->photos->count())
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                @foreach($album->photos as $photo)
                                    <div class="group cursor-pointer"
                                         onclick="openLightbox('{{ asset('storage/' . $photo->image_path) }}', '{{ addslashes($photo->title ?? $album->title) }}')">
                                        <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                            <img src="{{ asset('storage/' . $photo->image_path) }}"
                                                 alt="{{ $photo->title ?? $album->title }}"
                                                 class="w-full h-full object-cover group-hover:scale-110 transition duration-300"
                                                 loading="lazy">
                                        </div>
                                        @if($photo->title)
                                            <p class="text-xs text-gray-500 mt-1 truncate">{{ $photo->title }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-400 text-sm italic">No photos in this album yet.</p>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-20">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <h3 class="text-xl font-semibold text-gray-500 mb-2">No Albums Yet</h3>
                    <p class="text-gray-400">Photo albums will be added soon. Check back later!</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Lightbox Modal --}}
    <div id="lightbox" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center" onclick="closeLightbox()">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="max-w-5xl max-h-[90vh] p-4" onclick="event.stopPropagation()">
            <img id="lightbox-img" src="" alt="" class="max-w-full max-h-[80vh] mx-auto rounded-lg shadow-2xl">
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
