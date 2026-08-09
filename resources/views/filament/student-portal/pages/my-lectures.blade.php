{{--
    Lecture library with an embedded player and a grounded AI tutor.

    The lecture list and the open lecture are both re-queried against the
    student's own class in the page class, so nothing here can be widened by
    editing the ?lecture= parameter.
--}}
<x-filament-panels::page>
@include('filament.portal.styles')
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
                        {{--
                            Click-to-play, on purpose.

                            Embedding the player directly cost 5.8s on this page:
                            the iframe pulls in YouTube's whole runtime — measured
                            at 12 external requests totalling ~13.6s across
                            youtube-nocookie, gstatic, ytimg and ggpht — and the
                            browser blocks on it before the page settles.

                            The poster below is entirely local, so the page renders
                            immediately and nothing is requested from YouTube (and
                            no viewing data leaves the school) until the student
                            actually presses play. Then the real iframe is swapped
                            in with autoplay so it is still a single click.
                        --}}
                        <div class="sp-video" x-data="{ playing: false }">
                            <template x-if="playing">
                                <iframe
                                    src="{{ $embed }}&autoplay=1"
                                    title="{{ $lecture->title }}"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            </template>

                            <button
                                type="button"
                                x-show="! playing"
                                x-on:click="playing = true"
                                class="sp-video__poster"
                                aria-label="Play lecture: {{ $lecture->title }}"
                            >
                                <span class="sp-video__play" aria-hidden="true">&#9654;</span>
                                <span class="sp-video__label">Play lecture</span>
                                <span class="sp-video__hint">Loads from YouTube when you press play</span>
                            </button>
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

                @php($cards = $this->flashcards)
                @php($quiz = $this->practiceQuestions)

                @if ($cards->isNotEmpty())
                    <x-filament::section>
                        <x-slot name="heading">
                            <span class="inline-flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-m-rectangle-stack" class="h-4 w-4 text-primary-500" />
                                Revision cards
                            </span>
                        </x-slot>
                        <x-slot name="description">
                            Tap a card to reveal the answer. {{ $cards->count() }} cards for this lecture.
                        </x-slot>

                        <div
                            class="sp-cards"
                            x-data="{
                                i: 0,
                                shown: false,
                                seen: [],
                                total: {{ $cards->count() }},
                                cards: @js($cards->map(fn ($c) => ['front' => $c->front, 'back' => $c->back])->values()),
                                reveal() {
                                    this.shown = true;
                                    if (! this.seen.includes(this.i)) this.seen.push(this.i);
                                },
                                go(step) {
                                    this.i = (this.i + step + this.total) % this.total;
                                    this.shown = false;
                                },
                            }"
                        >
                            <button
                                type="button"
                                class="sp-card"
                                :class="shown && 'sp-card--flipped'"
                                x-on:click="shown ? go(1) : reveal()"
                                x-bind:aria-label="shown ? 'Next card' : 'Reveal answer'"
                            >
                                <span class="sp-card__side" x-text="shown ? 'Answer' : 'Question'"></span>
                                <span class="sp-card__text" x-text="cards[i][shown ? 'back' : 'front']"></span>
                                <span class="sp-card__hint" x-text="shown ? 'Tap for the next card' : 'Tap to reveal'"></span>
                            </button>

                            <div class="sp-cards__bar">
                                <button type="button" class="sp-cards__nav" x-on:click="go(-1)" aria-label="Previous card">&#8592;</button>
                                <div class="sp-cards__dots" aria-hidden="true">
                                    <template x-for="(c, n) in cards" :key="n">
                                        <span class="sp-cards__dot" :class="{ 'sp-cards__dot--on': n === i, 'sp-cards__dot--seen': seen.includes(n) }"></span>
                                    </template>
                                </div>
                                <button type="button" class="sp-cards__nav" x-on:click="go(1)" aria-label="Next card">&#8594;</button>
                            </div>

                            <p class="sp-cards__count">
                                Card <span x-text="i + 1"></span> of <span x-text="total"></span>
                                &middot; <span x-text="seen.length"></span> revealed
                            </p>
                        </div>
                    </x-filament::section>
                @endif

                @if ($quiz->isNotEmpty())
                    @php($checked = $this->quizChecked)
                    @php($score = $checked ? $this->quizScore() : 0)
                    <x-filament::section>
                        <x-slot name="heading">
                            <span class="inline-flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-m-academic-cap" class="h-4 w-4 text-primary-500" />
                                Practice quiz
                            </span>
                        </x-slot>
                        <x-slot name="description">
                            {{ $quiz->count() }} questions. Practice only &mdash; retry as often as you like, nothing here
                            affects your grades.
                        </x-slot>

                        @if ($checked)
                            @php($pct = (int) round($score / $quiz->count() * 100))
                            <div class="sp-quiz__result {{ $pct >= 100 ? 'sp-quiz__result--ace' : ($pct >= 60 ? 'sp-quiz__result--ok' : 'sp-quiz__result--low') }}">
                                <div class="sp-quiz__score">{{ $score }}<span>/{{ $quiz->count() }}</span></div>
                                <div>
                                    <div class="sp-quiz__verdict">
                                        @if ($pct === 100)
                                            Perfect &mdash; you have this one.
                                        @elseif ($pct >= 60)
                                            Good work. Review the ones you missed below.
                                        @else
                                            Worth another look &mdash; read the explanations, then try again.
                                        @endif
                                    </div>
                                    @if ($this->bestAttempt && $this->bestAttempt->score > $score)
                                        <div class="sp-quiz__best">Your best on this lecture: {{ $this->bestAttempt->score }}/{{ $this->bestAttempt->total }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div
                            class="sp-quiz"
                            x-data="{ answered: 0, total: {{ $quiz->count() }} }"
                            x-init="answered = $el.querySelectorAll('.sp-opt input:checked').length"
                            x-on:change="answered = $el.querySelectorAll('.sp-opt input:checked').length"
                        >
                            @foreach ($quiz as $n => $q)
                                @php($given = $this->answers[$q->id] ?? null)
                                @php($right = $this->isCorrect($q))
                                <div class="sp-quiz__q {{ $checked ? ($right ? 'sp-quiz__q--right' : 'sp-quiz__q--wrong') : '' }}">
                                    <p class="sp-quiz__text">
                                        <span class="sp-quiz__n">{{ $n + 1 }}</span>
                                        {{ $q->question_text }}
                                    </p>

                                    <div class="sp-quiz__opts">
                                        @foreach ($this->optionsFor($q) as $option)
                                            @php($isAnswer = mb_strtolower(trim($option)) === mb_strtolower(trim((string) $q->correct_answer)))
                                            <label class="sp-opt
                                                {{ $given === $option ? 'sp-opt--picked' : '' }}
                                                {{ $checked && $isAnswer ? 'sp-opt--answer' : '' }}
                                                {{ $checked && $given === $option && ! $isAnswer ? 'sp-opt--miss' : '' }}">
                                                <input
                                                    type="radio"
                                                    wire:model="answers.{{ $q->id }}"
                                                    value="{{ $option }}"
                                                    @disabled($checked)
                                                >
                                                <span>{{ $option }}</span>
                                                @if ($checked && $isAnswer)
                                                    <x-filament::icon icon="heroicon-m-check-circle" class="sp-opt__mark" />
                                                @elseif ($checked && $given === $option)
                                                    <x-filament::icon icon="heroicon-m-x-circle" class="sp-opt__mark" />
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>

                                    @if ($checked && filled($q->explanation))
                                        <p class="sp-quiz__why">
                                            <strong>{{ $right ? 'Correct.' : 'Why:' }}</strong> {{ $q->explanation }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach

                            <div class="sp-quiz__actions">
                                @if ($checked)
                                    <x-filament::button wire:click="resetQuiz" icon="heroicon-m-arrow-path" color="gray">
                                        Try again
                                    </x-filament::button>
                                @else
                                    <x-filament::button
                                        wire:click="checkQuiz"
                                        wire:loading.attr="disabled"
                                        icon="heroicon-m-check"
                                        x-bind:disabled="answered < total"
                                    >
                                        Check my answers
                                    </x-filament::button>
                                    <span class="sp-quiz__progress">
                                        <span x-text="answered"></span> of {{ $quiz->count() }} answered
                                    </span>
                                @endif
                            </div>
                        </div>
                    </x-filament::section>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
