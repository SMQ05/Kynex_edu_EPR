{{--
    Student portal dashboard.

    Every figure here comes from the page's own scoped queries, which read the
    signed-in student's id via ResolvesCurrentStudent. Nothing is taken from
    the request, so a student cannot widen the scope from the URL.
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
        $stats = $this->stats;
        $currency = \App\Support\SchoolSettings::get('currency.symbol', '$');
    @endphp

    {{-- ── Headline counters ───────────────────────────────────────── --}}
    
{{--
    Filament's compiled stylesheet ships layout and typography utilities but
    NOT the colour scale (text-primary-600, text-gray-500, text-green-600 …
    are all absent — verified against public/css/filament/filament/app.css),
    and not grid-cols-*. Rather than depend on a Tailwind rebuild inside the
    container, the handful of colours and the grid this page needs are defined
    here. `.dark` is the class Filament puts on <html> for dark mode.
--}}
<style>
    .sp-grid-stats { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .sp-grid-two   { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }

    .sp-stat        { text-align: center; }
    .sp-stat__value { font-size: 1.875rem; font-weight: 700; line-height: 1.25; }
    .sp-stat__label { margin-top: .25rem; font-size: .875rem; color: #6b7280; }
    .sp-stat__hint  { display: block; font-size: .75rem; color: #9ca3af; }
    .dark .sp-stat__label { color: #9ca3af; }
    .dark .sp-stat__hint  { color: #6b7280; }

    .sp-teal  { color: #0d9488; }  .dark .sp-teal  { color: #2dd4bf; }
    .sp-good  { color: #16a34a; }  .dark .sp-good  { color: #4ade80; }
    .sp-warn  { color: #d97706; }  .dark .sp-warn  { color: #fbbf24; }
    .sp-bad   { color: #dc2626; }  .dark .sp-bad   { color: #f87171; }
    .sp-ink   { color: #111827; }  .dark .sp-ink   { color: #f9fafb; }

    .sp-row      { display: flex; align-items: flex-start; justify-content: space-between;
                   gap: .75rem; padding: .75rem 0; border-bottom: 1px solid #f3f4f6; }
    .sp-row:last-child { border-bottom: 0; }
    .dark .sp-row      { border-bottom-color: #1f2937; }
    .sp-row__title { font-weight: 500; color: #111827; }
    .dark .sp-row__title { color: #f9fafb; }
    .sp-row__meta  { font-size: .75rem; color: #6b7280; }
    .dark .sp-row__meta { color: #9ca3af; }
    .sp-empty { padding: 1.5rem 0; text-align: center; font-size: .875rem; color: #6b7280; }

    .sp-badge      { flex-shrink: 0; border-radius: 9999px; padding: .125rem .5rem;
                     font-size: .75rem; font-weight: 500; white-space: nowrap; }
    .sp-badge--due { background: #f3f4f6; color: #374151; }
    .sp-badge--late{ background: #fee2e2; color: #b91c1c; }
    .sp-badge--exam{ background: #ccfbf1; color: #0f766e; }
    .dark .sp-badge--due  { background: #1f2937; color: #d1d5db; }
    .dark .sp-badge--late { background: rgba(127,29,29,.4); color: #fca5a5; }
    .dark .sp-badge--exam { background: rgba(19,78,74,.5); color: #5eead4; }
</style>

    <div class="sp-grid-stats">
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-teal">
                {{ $stats['attendance_rate'] !== null ? $stats['attendance_rate'] . '%' : '—' }}
            </div>
            <div class="sp-stat__label">
                Attendance
                @if ($stats['attendance_days'] > 0)
                    <span class="sp-stat__hint">across {{ number_format($stats['attendance_days']) }} days</span>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section class="sp-stat">
            <div class="sp-stat__value {{ $stats['pending_assignments'] > 0 ? 'sp-warn' : 'sp-good' }}">
                {{ $stats['pending_assignments'] }}
            </div>
            <div class="sp-stat__label">
                Assignments to do
                <span class="sp-stat__hint">nothing submitted yet</span>
            </div>
        </x-filament::section>

        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-ink">
                {{ $stats['latest_grade'] ?? '—' }}
            </div>
            <div class="sp-stat__label">
                Latest exam grade
                @if ($stats['latest_percentage'] !== null)
                    <span class="sp-stat__hint">{{ $stats['latest_percentage'] }}% overall</span>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section class="sp-stat">
            <div class="sp-stat__value {{ $stats['outstanding_fees'] > 0 ? 'sp-bad' : 'sp-good' }}">
                {{ $currency }}{{ number_format($stats['outstanding_fees'] / 100, 2) }}
            </div>
            <div class="sp-stat__label">
                Balance due
                <span class="sp-stat__hint">{{ $stats['outstanding_fees'] > 0 ? 'payment outstanding' : 'all settled' }}</span>
            </div>
        </x-filament::section>
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
