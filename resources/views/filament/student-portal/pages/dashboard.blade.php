{{--
    Student portal dashboard.

    Every figure here comes from the page's own scoped queries, which read the
    signed-in student's id via ResolvesCurrentStudent. Nothing is taken from
    the request, so a student cannot widen the scope from the URL.
--}}
<x-filament-panels::page>
@include('filament.student-portal.partials.styles')

    @php
        $stats = $this->stats;
        $currency = \App\Support\SchoolSettings::get('currency.symbol', '$');
    @endphp

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
