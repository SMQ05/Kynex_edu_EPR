{{--
    Parent portal — one card per child.

    Rewritten because the previous version was written against Tailwind classes
    that are absent from Filament's compiled stylesheet (678 of the 699 classes
    used across this project's Filament views are missing), so it rendered as an
    unstyled wall of text. It now uses the shared portal stylesheet, which
    defines what it needs explicitly.

    Data comes from Dashboard::children() and ::summaryFor(), both of which
    resolve through the signed-in guardian's own links — a parent cannot widen
    the scope from the URL.
--}}
<x-filament-panels::page>
@include('filament.portal.styles', ['accent' => 'indigo'])

@php
    $currency = \App\Support\SchoolSettings::get('currency.symbol', '$');
    $children = $this->children;
@endphp

@if ($children->isEmpty())
    <x-filament::section>
        <p class="sp-empty">
            No children are linked to this account yet. Contact the school office and
            they can connect your record to your child's.
        </p>
    </x-filament::section>
@else
    @foreach ($children as $child)
        @php
            $s = $this->summaryFor($child);
            $att = $s['attendancePct'];
            $res = $s['latestResult'];
            $fees = $this->feeSummaryFor($child);
        @endphp

        <x-filament::section>
            {{-- ── Child header ────────────────────────────────────── --}}
            <div class="pp-head">
                <div class="pp-avatar">{{ $this->initialsFor($child) }}</div>
                <div style="min-width:0;flex:1;">
                    <div class="pp-name">{{ trim($child->first_name . ' ' . $child->last_name) }}</div>
                    <div class="sp-row__meta">
                        {{ $child->schoolClass?->name ?? 'Unassigned' }}
                        @if ($child->section) · Section {{ $child->section->name }} @endif
                        @if ($child->roll_number) · Roll {{ $child->roll_number }} @endif
                        · ID {{ $child->admission_number ?: $child->registration_number ?: '—' }}
                    </div>
                </div>
            </div>

            {{-- ── At a glance ─────────────────────────────────────── --}}
            <div class="sp-grid-stats" style="margin-top:1rem;">
                <div class="sp-stat">
                    <div class="sp-stat__value {{ $att === null ? 'sp-mute' : ($att >= 90 ? 'sp-good' : ($att >= 80 ? 'sp-warn' : 'sp-bad')) }}">
                        {{ $att !== null ? $att . '%' : '—' }}
                    </div>
                    <div class="sp-stat__label">Attendance</div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value sp-ink">{{ $res->grade ?? '—' }}</div>
                    <div class="sp-stat__label">
                        Latest grade
                        @if ($res?->percentage !== null)
                            <span class="sp-stat__hint">{{ round((float) $res->percentage, 1) }}% overall</span>
                        @endif
                    </div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value sp-ink">{{ $res->rank ?? '—' }}</div>
                    <div class="sp-stat__label">Class rank</div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value {{ $fees['due'] > 0 ? 'sp-bad' : 'sp-good' }}">
                        {{ $currency }}{{ number_format($fees['due'] / 100, 2) }}
                    </div>
                    <div class="sp-stat__label">
                        Balance due
                        <span class="sp-stat__hint">{{ $fees['due'] > 0 ? 'payment outstanding' : 'all settled' }}</span>
                    </div>
                </div>
            </div>

            @if ($att !== null)
                <div class="sp-bar" style="margin-top:1rem;" title="{{ $att }}% attendance">
                    <div class="sp-bar__fill" style="width: {{ $att }}%;"></div>
                </div>
            @endif

            {{-- ── Fees + pay ──────────────────────────────────────── --}}
            @if ($fees['due'] > 0)
                <div class="pp-callout">
                    <div style="min-width:0;">
                        <div class="pp-callout__title">
                            {{ $currency }}{{ number_format($fees['due'] / 100, 2) }} outstanding
                            @if ($fees['overdue'] > 0)
                                <span class="sp-badge sp-badge--late" style="margin-left:.375rem;">
                                    {{ $currency }}{{ number_format($fees['overdue'] / 100, 2) }} overdue
                                </span>
                            @endif
                        </div>
                        <div class="sp-row__meta">
                            Across {{ $fees['lines'] }} {{ Str::plural('item', $fees['lines']) }}.
                            @if ($fees['nextDue'])
                                Next due {{ $fees['nextDue']->format('M j, Y') }}.
                            @endif
                        </div>
                    </div>
                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\ParentPortal\Pages\Fees::getUrl(['child' => $child->id])"
                        icon="heroicon-m-credit-card"
                    >
                        Pay fees
                    </x-filament::button>
                </div>
            @endif

            {{-- ── Two columns of detail ───────────────────────────── --}}
            <div class="sp-grid-two" style="margin-top:1.25rem;">
                <div>
                    <h4 class="pp-sub">Homework due</h4>
                    @forelse ($s['upcomingHomework'] as $hw)
                        @php $due = $hw->due_date ? \Illuminate\Support\Carbon::parse($hw->due_date) : null; @endphp
                        <div class="sp-row">
                            <div style="min-width:0;">
                                <div class="sp-row__title truncate">{{ $hw->title }}</div>
                                <div class="sp-row__meta">{{ $hw->subject?->name ?? 'General' }}</div>
                            </div>
                            <span class="sp-badge sp-badge--due">{{ $due ? $due->format('M j') : 'TBC' }}</span>
                        </div>
                    @empty
                        <p class="sp-empty">Nothing due right now.</p>
                    @endforelse
                </div>

                <div>
                    <h4 class="pp-sub">Upcoming tests</h4>
                    @forelse ($s['upcomingExams'] as $ex)
                        @php $d = $ex['at'] ? \Illuminate\Support\Carbon::parse($ex['at']) : null; @endphp
                        <div class="sp-row">
                            <div style="min-width:0;">
                                <div class="sp-row__title truncate">{{ $ex['subject'] ?? 'Subject' }}</div>
                                <div class="sp-row__meta">
                                    {{ $ex['title'] }}@if ($ex['detail']) · {{ $ex['detail'] }}@endif
                                </div>
                            </div>
                            <div class="pp-when">
                                <span class="sp-badge {{ $ex['kind'] === 'open now' ? 'sp-badge--live' : 'sp-badge--exam' }}">
                                    {{ $ex['kind'] === 'open now' ? 'Open now' : ($d ? $d->format('M j') : 'TBC') }}
                                </span>
                                @if ($d && $ex['kind'] !== 'open now')
                                    <span class="pp-countdown">{{ $d->isToday() ? 'today' : 'in ' . max(1, (int) ceil(now()->diffInDays($d, false))) . 'd' }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="sp-empty">No tests scheduled.</p>
                    @endforelse
                </div>
            </div>

            {{-- Course progress against the published syllabus --}}
            @if (! empty($s['courses']))
                <h4 class="pp-sub" style="margin-top:1.25rem;">Course progress</h4>
                <div class="pp-courses">
                    @foreach ($s['courses'] as $course)
                        <div class="pp-course">
                            <div class="pp-course__head">
                                <span class="pp-course__name">{{ $course['subject'] }}</span>
                                <span class="pp-course__pct">{{ $course['pct'] }}%</span>
                            </div>
                            <div class="pp-bar"><span style="width: {{ max(2, $course['pct']) }}%"></span></div>
                            <div class="pp-course__meta">
                                {{ $course['done'] }} of {{ $course['total'] }} units taught
                                @if ($course['lectures'] > 0)
                                    · {{ $course['lectures'] }} {{ \Illuminate\Support\Str::plural('recording', $course['lectures']) }}
                                @endif
                            </div>
                            @if ($course['current'])
                                <div class="pp-course__now">Now: {{ $course['current'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ── Recent marks ────────────────────────────────────── --}}
            @if ($s['recentMarks']->isNotEmpty())
                <h4 class="pp-sub" style="margin-top:1.25rem;">Recent exam marks</h4>
                <div class="sp-table__scroll">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th>Exam</th><th>Subject</th>
                                <th class="sp-num">Marks</th><th class="sp-num">Out of</th><th class="sp-num">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($s['recentMarks'] as $m)
                                @php
                                    $full = $m->schedule?->full_marks;
                                    $got  = $m->is_absent ? null : $m->marks_obtained;
                                    $pc   = ($got !== null && $full) ? round($got / $full * 100, 1) : null;
                                @endphp
                                <tr>
                                    <td>{{ $m->schedule?->exam?->name ?? '—' }}</td>
                                    <td>{{ $m->schedule?->subject?->name ?? '—' }}</td>
                                    <td class="sp-num">{{ $got !== null ? (int) $got : '—' }}</td>
                                    <td class="sp-num">{{ $full ? (int) $full : '—' }}</td>
                                    <td class="sp-num {{ $pc !== null && $pc < 60 ? 'sp-bad' : '' }}">{{ $pc !== null ? $pc . '%' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- ── Homework grades ─────────────────────────────────── --}}
            @if ($s['recentHomeworkMarks']->isNotEmpty())
                <h4 class="pp-sub" style="margin-top:1.25rem;">Recent homework grades</h4>
                @foreach ($s['recentHomeworkMarks'] as $hs)
                    @php
                        $of = $hs->total_marks ?: $hs->homework?->total_marks;
                        $pc = ($hs->marks_obtained !== null && $of) ? round($hs->marks_obtained / $of * 100) : null;
                    @endphp
                    <div class="sp-row">
                        <div style="min-width:0;">
                            <div class="sp-row__title truncate">{{ $hs->homework?->title ?? 'Assignment' }}</div>
                            <div class="sp-row__meta">{{ $hs->homework?->subject?->name ?? 'General' }}</div>
                        </div>
                        <span class="sp-badge {{ $pc !== null && $pc >= 60 ? 'sp-badge--ok' : 'sp-badge--late' }}">
                            {{ $hs->marks_obtained }}@if ($of)/{{ $of }}@endif
                        </span>
                    </div>
                @endforeach
            @endif
        </x-filament::section>
    @endforeach
@endif

<style>
    /* Parent-specific pieces, on top of the shared portal sheet. */
    .pp-head   { display: flex; align-items: center; gap: 1rem; }
    .pp-avatar { width: 3.5rem; height: 3.5rem; flex-shrink: 0; border-radius: .75rem;
                 display: flex; align-items: center; justify-content: center;
                 font-size: 1.125rem; font-weight: 700; color: #fff;
                 background: linear-gradient(135deg, var(--portal-accent-deep), var(--portal-accent)); }
    .pp-name   { font-size: 1.125rem; font-weight: 700; color: #111827; }
    .dark .pp-name { color: #f9fafb; }
    .pp-sub    { font-size: .8125rem; font-weight: 600; text-transform: uppercase;
                 letter-spacing: .04em; color: #6b7280; margin: 0 0 .25rem; }
    .dark .pp-sub { color: #9ca3af; }
    .pp-callout { display: flex; align-items: center; justify-content: space-between;
                  gap: 1rem; flex-wrap: wrap; margin-top: 1rem; padding: .875rem 1rem;
                  border-radius: .75rem; background: #fef2f2; border: 1px solid #fecaca; }
    .dark .pp-callout { background: rgba(127,29,29,.25); border-color: rgba(185,28,28,.5); }
    .pp-callout__title { font-weight: 600; color: #991b1b; }
    .dark .pp-callout__title { color: #fca5a5; }
</style>
</x-filament-panels::page>
