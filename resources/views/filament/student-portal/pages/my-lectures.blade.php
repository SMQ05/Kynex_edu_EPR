{{--
    Lecture library with an embedded player and a grounded AI tutor.

    The lecture list and the open lecture are both re-queried against the
    student's own class in the page class, so nothing here can be widened by
    editing the ?lecture= parameter.
--}}
<x-filament-panels::page>
@include('filament.student-portal.partials.styles')
{{--
    NOTE ON LAYOUT: this page uses inline CSS grid rather than Tailwind's
    grid-cols-* utilities. Those utilities are NOT present in Filament's
    compiled stylesheet (verified against public/css/filament/filament/app.css),
    so `grid grid-cols-4` silently renders as a plain stacked block. Inline
    `repeat(auto-fit, minmax(...))` is responsive without a media query and
    without depending on a Tailwind rebuild.
--}}

    @php
        $lecture = $this->lecture;
        $embed = $this->embedUrl;
    @endphp

    <div style="display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));align-items:start;">
        {{-- ── Lecture list ────────────────────────────────────────── --}}
        <div>
            <x-filament::section>
                <x-slot name="heading">Library</x-slot>
                <x-slot name="description">{{ $this->lectures->count() }} published</x-slot>

                <div class="sp-scroll"><div class="sp-list">
                    @forelse ($this->lectures as $item)
                        <button
                            type="button"
                            wire:click="selectLecture('{{ $item->id }}')"
class="sp-list__item {{ $lecture && $lecture->id === $item->id ? 'sp-list__item--on' : '' }}"
                        >
                            <div class="sp-list__title">
                                {{ $item->title }}
                            </div>
                            <div class="sp-list__meta">
                                <span>{{ $item->subject?->name ?? 'General' }}</span>
                                @if ($item->source_type === 'link' || $item->external_url)
                                    <span aria-hidden="true">·</span>
                                    <span style="display:inline-flex;align-items:center;gap:.15rem;">
                                        <x-filament::icon icon="heroicon-m-play-circle" class="h-3 w-3" />
                                        Video
                                    </span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <p class="sp-empty">
                            No lectures published yet.
                        </p>
                    @endforelse
                </div></div>
            </x-filament::section>
        </div>

        {{-- ── Player + notes + tutor ──────────────────────────────── --}}
        <div class="space-y-6" style="grid-column:span 2;min-width:0;">
            @if (! $lecture)
                <x-filament::section>
                    <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        Select a lecture to begin.
                    </p>
                </x-filament::section>
            @else
                <x-filament::section>
                    <x-slot name="heading">{{ $lecture->title }}</x-slot>
                    <x-slot name="description">
                        {{ $lecture->subject?->name ?? 'General' }}
                        @if ($lecture->teacher)
                            · {{ $lecture->teacher->name }}
                        @endif
                    </x-slot>

                    @if ($embed)
                        <div class="sp-video">
                            <iframe
                                src="{{ $embed }}"
                                title="{{ $lecture->title }}"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                    @elseif ($lecture->external_url)
                        <x-filament::link :href="$lecture->external_url" target="_blank" rel="noopener">
                            Open lecture material
                        </x-filament::link>
                    @endif

                    @if (filled($lecture->description))
                        <div class="mt-4">
                            <h4 class="sp-notes__h">Lesson notes</h4>
                            <div class="sp-notes">{{ $lecture->description }}</div>
                        </div>
                    @endif
                </x-filament::section>

                {{-- ── AI tutor ────────────────────────────────────── --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <span class="inline-flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-m-sparkles" class="h-4 w-4 text-primary-500" />
                            Ask about this lecture
                        </span>
                    </x-slot>
                    <x-slot name="description">
                        Answers are based on this lecture's notes. If something is not covered, the tutor will say so.
                    </x-slot>

                    @if (! $this->aiAvailable())
                        <div class="sp-note-box">
                            {{ $this->aiUnavailableReason() ?? 'AI is currently unavailable.' }}
                        </div>
                    @else
                        @if ($this->messages->isNotEmpty())
                            <div class="sp-chat">
                                @foreach ($this->messages as $message)
                                    @if ($message->role === 'user')
                                        <div class="sp-chat__row sp-chat__row--me">
                                            <div class="sp-chat__bubble sp-chat__bubble--me">
                                                {{ $message->content }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="sp-chat__row">
                                            <div class="sp-chat__bubble sp-chat__bubble--ai">
                                                {{ $message->content }}
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <form wire:submit="ask" class="sp-ask">
                            <textarea
                                wire:model="question"
                                rows="2"
                                placeholder="e.g. Can you explain the second part again more simply?"
                                class="sp-ask__box"
                                @disabled($this->thinking)
                            ></textarea>
                            <x-filament::button
                                type="submit"
                                icon="heroicon-m-paper-airplane"
                                wire:loading.attr="disabled"
                                wire:target="ask"
                            >
                                <span wire:loading.remove wire:target="ask">Ask</span>
                                <span wire:loading wire:target="ask">Thinking…</span>
                            </x-filament::button>
                        </form>
                    @endif
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
