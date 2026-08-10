{{--
    Student portal dashboard.

    Laid out to the KynexEdu redesign: what is happening right now, what is due,
    where this student stands, which subjects need work, and one concrete next
    step. Every figure is read from this student's own records through
    ResolvesCurrentStudent — nothing is taken from the request, so the scope
    cannot be widened from the URL.
--}}
<x-filament-panels::page>
@include('filament.portal.styles')

    @php
        $stats = $this->stats;
        $now = $this->rightNow;
        $standing = $this->standing;
        $subjects = $this->subjectPerformance;
        $coach = $this->coach;
        $student = $this->me;
        $currency = \App\Support\SchoolSettings::get('currency.symbol', '$');
    @endphp

    {{-- ── Right now, and what is due ───────────────────────────────── --}}
    <div class="sp-top">
        <div class="sp-live">
            @if ($now)
                <div class="sp-live__eyebrow">
                    @if ($now['state'] === 'now')
                        Right now @if ($now['period']) &middot; Period {{ $now['period'] }} @endif
                    @elseif ($now['state'] === 'break')
                        On break now
                    @elseif ($now['day'])
                        First lesson {{ $now['day'] }}
                    @else
                        Up next @if ($now['period']) &middot; Period {{ $now['period'] }} @endif
                    @endif
                </div>
                <div class="sp-live__title">{{ $now['subject'] ?? 'Free period' }}</div>
                <div class="sp-live__meta">
                    @if ($now['teacher']) {{ $now['teacher'] }} &middot; @endif
                    @if ($now['state'] === 'next') starts {{ $now['starts'] }} @else ends {{ $now['ends'] }} @endif
                    @if ($now['room']) &middot; {{ $now['room'] }} @endif
                    @if ($now['next']) &middot; then {{ $now['next'] }} @endif
                </div>
            @else
                <div class="sp-live__eyebrow">
                    {{ $student?->schoolClass?->name ?? 'Your class' }}@if ($student?->section) &middot; Section {{ $student->section->name }} @endif
                </div>
                <div class="sp-live__title">No class scheduled now</div>
                <div class="sp-live__meta">Your timetable picks up again on the next school day.</div>
            @endif

            <div class="sp-live__actions">
                <a class="sp-live__btn" href="{{ \App\Filament\StudentPortal\Pages\MyLectures::getUrl() }}">Today's material</a>
                <a class="sp-live__btn sp-live__btn--ghost" href="{{ \App\Filament\StudentPortal\Pages\MyCourses::getUrl() }}">Practice weak topics</a>
            </div>
        </div>

        <x-filament::section>
            <x-slot name="heading">Due this week</x-slot>

            @forelse ($this->upcomingAssignments->take(4) as $hw)
                @php
                    $due = $hw->due_date ? \Illuminate\Support\Carbon::parse($hw->due_date) : null;
                    $overdue = $due && $due->isPast();
                    $soon = $due && ! $overdue && $due->lessThanOrEqualTo(now()->addDays(2)->endOfDay());
                    $tone = $overdue ? 'late' : ($soon ? 'soon' : 'calm');
                @endphp
                <div class="sp-due">
                    <span class="sp-due__ic sp-due__ic--{{ $tone }}">
                        <x-filament::icon icon="heroicon-m-document-text" class="h-4 w-4" />
                    </span>
                    <div class="sp-due__body">
                        <div class="sp-due__title">{{ $hw->title }}</div>
                        <div class="sp-due__when sp-due__when--{{ $tone }}">
                            @if ($due)
                                @if ($overdue)
                                    Overdue &middot; was {{ $due->format('M j') }}
                                @elseif ($due->isToday())
                                    Due today
                                @elseif ($due->isTomorrow())
                                    Due tomorrow
                                @else
                                    Due {{ $due->format('l') }}
                                @endif
                            @else
                                No due date
                            @endif
                        </div>
                    </div>
                    <a class="sp-due__go {{ $overdue || $soon ? 'sp-due__go--solid' : '' }}"
                       href="{{ \App\Filament\StudentPortal\Pages\MyAssignments::getUrl() }}">
                        {{ $overdue || $soon ? 'Submit' : 'Open' }}
                    </a>
                </div>
            @empty
                <p class="sp-empty">Nothing due this week.</p>
            @endforelse
        </x-filament::section>
    </div>

    {{-- ── Where this student stands ────────────────────────────────── --}}
    <div class="sp-grid-stats">
        <x-filament::section class="sp-stat">
            <div class="sp-stat__label">My attendance</div>
            <div class="sp-stat__value">{{ $stats['attendance_rate'] !== null ? $stats['attendance_rate'] . '%' : '—' }}</div>
            @if ($stats['attendance_rate'] !== null)
                <div class="sp-meter">
                    <span class="sp-meter__fill {{ $stats['attendance_rate'] >= 90 ? 'sp-meter__fill--good' : ($stats['attendance_rate'] >= 75 ? 'sp-meter__fill--warn' : 'sp-meter__fill--bad') }}"
                          style="width: {{ max(2, min(100, (float) $stats['attendance_rate'])) }}%"></span>
                </div>
                <span class="sp-stat__hint">across {{ number_format($stats['attendance_days']) }} days</span>
            @endif
        </x-filament::section>

        <x-filament::section class="sp-stat">
            <div class="sp-stat__label">{{ $standing['exam'] ? $standing['exam'] . ' average' : 'Term average' }}</div>
            <div class="sp-stat__value">{{ $standing['percent'] !== null ? $standing['percent'] . '%' : '—' }}</div>
            @if ($standing['delta'] !== null)
                <span class="sp-delta {{ $standing['delta'] < 0 ? 'sp-delta--down' : 'sp-delta--up' }}">
                    {{ $standing['delta'] < 0 ? '▼' : '▲' }} {{ abs($standing['delta']) }} pts vs {{ $standing['previous'] }}
                </span>
            @elseif ($standing['percent'] !== null)
                <span class="sp-stat__hint">first published result</span>
            @endif
        </x-filament::section>

        <x-filament::section class="sp-stat">
            <div class="sp-stat__label">Class position</div>
            <div class="sp-stat__value">{{ $standing['rank'] ?? '—' }}@if ($standing['rank'] && $standing['outOf'])<span class="sp-stat__of">/{{ $standing['outOf'] }}</span>@endif</div>
            <span class="sp-stat__hint">{{ $standing['exam'] ? $standing['exam'] . ' standing' : 'no published result yet' }}</span>
        </x-filament::section>

        <x-filament::section class="sp-stat">
            <div class="sp-stat__label">Fee status</div>
            <div class="sp-stat__value sp-stat__value--sm {{ $stats['outstanding_fees'] > 0 ? 'sp-bad' : 'sp-good' }}">
                {{ $stats['outstanding_fees'] > 0
                    ? $currency . number_format($stats['outstanding_fees'] / 100, 2)
                    : 'All settled' }}
            </div>
            <span class="sp-stat__hint">{{ $stats['outstanding_fees'] > 0 ? 'outstanding balance' : 'nothing due' }}</span>
        </x-filament::section>
    </div>

    {{-- ── Subject performance, and one next step ───────────────────── --}}
    <div class="sp-two-uneven">
        <x-filament::section>
            {{-- Built in PHP: a Blade @if inside an x-slot breaks the component's
                 own compiled if/endif pairing and derails the whole file. --}}
            <x-slot name="heading">{{ 'Subject performance' . ($standing['exam'] ? ' · ' . $standing['exam'] : '') }}</x-slot>

            @forelse ($subjects as $row)
                @php $band = $row['percent'] >= 75 ? 'good' : ($row['percent'] >= 60 ? 'warn' : 'bad'); @endphp
                <div class="sp-perf">
                    <div class="sp-perf__head">
                        <span>{{ $row['subject'] }}</span>
                        <span class="sp-perf__pct sp-{{ $band }}">{{ $row['percent'] }}%</span>
                    </div>
                    <div class="sp-meter">
                        <span class="sp-meter__fill sp-meter__fill--{{ $band }}"
                              style="width: {{ max(2, min(100, $row['percent'])) }}%"></span>
                    </div>
                </div>
            @empty
                <p class="sp-empty">No published exam marks yet.</p>
            @endforelse
        </x-filament::section>

        <div class="sp-coach">
            <div class="sp-coach__head">
                <span class="sp-coach__mark"></span>
                <span class="sp-coach__title">Your study coach</span>
            </div>

            @if ($coach)
                <p class="sp-coach__body">
                    <strong>{{ $coach['subject'] }}</strong> is your weakest subject at {{ $coach['percent'] }}%@if ($coach['topic']), and your class is on <strong>{{ $coach['topic'] }}</strong>@endif.
                    Fifteen minutes of practice a day should close it before the next exam.
                </p>
                <a class="sp-coach__cta"
                   href="{{ $coach['lecture']
                        ? \App\Filament\StudentPortal\Pages\MyLectures::getUrl(['lecture' => $coach['lecture']])
                        : \App\Filament\StudentPortal\Pages\MyCourses::getUrl() }}">
                    Start today's 15 minutes
                </a>
            @else
                <p class="sp-coach__body">
                    Nothing to flag right now — either your marks are holding up across every subject, or
                    there is not enough marked work yet to say something useful. Practice stays open either way.
                </p>
                <a class="sp-coach__cta" href="{{ \App\Filament\StudentPortal\Pages\MyCourses::getUrl() }}">Open my courses</a>
            @endif
        </div>
    </div>

    <div class="sp-grid-two">
        {{-- ── Assignments due ─────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Assignments due</x-slot>
            <x-slot name="description">Soonest first. Overdue items are flagged.</x-slot>

            @forelse ($this->upcomingAssignments as $hw)
                @php
                    $due = $hw->due_date ? \Illuminate\Support\Carbon::parse($hw->due_date) : null;
                    $overdue = $due && $due->isPast();
                @endphp
                <div class="sp-row">
                    <div class="min-w-0">
                        <div class="sp-row__title truncate">{{ $hw->title }}</div>
                        <div class="sp-row__meta">
                            {{ $hw->subject?->name ?? 'General' }}
                            @if ($hw->total_marks)
                                · {{ $hw->total_marks }} marks
                            @endif
                        </div>
                    </div>
                    <span class="sp-badge {{ $overdue ? 'sp-badge--late' : 'sp-badge--due' }}">
                        {{ $due ? ($overdue ? 'Overdue ' . $due->diffForHumans(short: true) : 'Due ' . $due->format('M j')) : 'No due date' }}
                    </span>
                </div>
            @empty
                <p class="sp-empty">
                    Nothing outstanding. You are caught up.
                </p>
            @endforelse
        </x-filament::section>

        {{-- ── Upcoming exams ──────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Upcoming exams</x-slot>
            <x-slot name="description">Scheduled for your class.</x-slot>

            @forelse ($this->upcomingExams as $sched)
                @php $date = $sched->exam_date ? \Illuminate\Support\Carbon::parse($sched->exam_date) : null; @endphp
                <div class="sp-row">
                    <div class="min-w-0">
                        <div class="sp-row__title truncate">
                            {{ $sched->subject?->name ?? 'Subject' }}
                        </div>
                        <div class="sp-row__meta">
                            {{ $sched->exam?->name ?? 'Exam' }}
                            @if ($sched->room)
                                · Room {{ $sched->room }}
                            @endif
                            @if ($sched->full_marks)
                                · {{ $sched->full_marks }} marks
                            @endif
                        </div>
                    </div>
                    <span class="sp-badge sp-badge--exam">
                        {{ $date ? $date->format('M j') : 'TBC' }}
                    </span>
                </div>
            @empty
                <p class="sp-empty">
                    No exams scheduled right now.
                </p>
            @endforelse
        </x-filament::section>
    </div>

    {{-- ── Recent lectures ─────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Recent lectures</x-slot>
        <x-slot name="description">Newly published material for your class.</x-slot>

        @forelse ($this->recentLectures as $lecture)
            <div class="sp-row" style="align-items:center;">
                <div class="min-w-0">
                    <div class="sp-row__title truncate">{{ $lecture->title }}</div>
                    <div class="sp-row__meta">
                        {{ $lecture->subject?->name ?? 'General' }}
                        @if ($lecture->category)
                            · {{ ucfirst($lecture->category) }}
                        @endif
                    </div>
                </div>
                <x-filament::link
                    :href="\App\Filament\StudentPortal\Pages\MyLectures::getUrl(['lecture' => $lecture->id])"
                    size="sm"
                >
                    Open
                </x-filament::link>
            </div>
        @empty
            <p class="sp-empty">
                No lectures published yet.
            </p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
