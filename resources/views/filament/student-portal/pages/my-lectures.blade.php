{{--
    Lecture library with an embedded player and a grounded AI tutor.

    The lecture list and the open lecture are both re-queried against the
    student's own class in the page class, so nothing here can be widened by
    editing the ?lecture= parameter.
--}}
<x-filament-panels::page>
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

                <div class="-mx-2 max-h-[32rem] space-y-1 overflow-y-auto">
                    @forelse ($this->lectures as $item)
                        <button
                            type="button"
                            wire:click="selectLecture('{{ $item->id }}')"
                            @class([
                                'w-full rounded-lg px-3 py-2 text-left transition',
                                'bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-900/30' => $lecture && $lecture->id === $item->id,
                                'hover:bg-gray-50 dark:hover:bg-gray-800' => ! ($lecture && $lecture->id === $item->id),
                            ])
                        >
                            <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ $item->title }}
                            </div>
                            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $item->subject?->name ?? 'General' }}</span>
                                @if ($item->source_type === 'external_url' || $item->external_url)
                                    <span aria-hidden="true">·</span>
                                    <span class="inline-flex items-center gap-0.5">
                                        <x-filament::icon icon="heroicon-m-play-circle" class="h-3 w-3" />
                                        Video
                                    </span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <p class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            No lectures published yet.
                        </p>
                    @endforelse
                </div>
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
                        <div class="overflow-hidden rounded-lg bg-black" style="aspect-ratio: 16 / 9;">
                            <iframe
                                src="{{ $embed }}"
                                title="{{ $lecture->title }}"
                                class="h-full w-full"
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
                            <h4 class="mb-1 text-sm font-semibold text-gray-900 dark:text-white">Lesson notes</h4>
                            <div class="prose prose-sm max-w-none whitespace-pre-line text-gray-700 dark:prose-invert dark:text-gray-300">{{ $lecture->description }}</div>
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
                        <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                            {{ $this->aiUnavailableReason() ?? 'AI is currently unavailable.' }}
                        </div>
                    @else
                        @if ($this->messages->isNotEmpty())
                            <div class="mb-4 max-h-96 space-y-3 overflow-y-auto">
                                @foreach ($this->messages as $message)
                                    @if ($message->role === 'user')
                                        <div class="flex justify-end">
                                            <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-primary-600 px-3 py-2 text-sm text-white">
                                                {{ $message->content }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex justify-start">
                                            <div class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-bl-sm bg-gray-100 px-3 py-2 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                {{ $message->content }}
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <form wire:submit="ask" class="flex items-end gap-2">
                            <div class="flex-1">
                                <textarea
                                    wire:model="question"
                                    rows="2"
                                    placeholder="e.g. Can you explain the second part again more simply?"
                                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    @disabled($this->thinking)
                                ></textarea>
                            </div>
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
