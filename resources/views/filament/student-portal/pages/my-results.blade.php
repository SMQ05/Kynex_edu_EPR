{{--
    Report card. Each completed exam shows its aggregate grade and rank, then
    the per-subject marks that produced it. All scoped to the signed-in student.
--}}
<x-filament-panels::page>
@include('filament.student-portal.partials.styles')

    @php $att = $this->attendance; @endphp

    {{-- ── Attendance summary ──────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Attendance</x-slot>
        <x-slot name="description">
            {{ $att['total'] > 0 ? number_format($att['total']) . ' days recorded' : 'Nothing recorded yet' }}
        </x-slot>

        @if ($att['total'] > 0)
            <div style="display:flex;align-items:baseline;gap:.5rem;margin-bottom:.75rem;">
                <span class="sp-stat__value {{ $att['rate'] >= 90 ? 'sp-good' : ($att['rate'] >= 80 ? 'sp-warn' : 'sp-bad') }}">{{ $att['rate'] }}%</span>
                <span class="sp-mute" style="font-size:.875rem;">present or late</span>
            </div>
            <div class="sp-bar" title="{{ $att['rate'] }}% attendance">
                <div class="sp-bar__fill" style="width: {{ $att['rate'] }}%;"></div>
            </div>
            <div style="display:flex;gap:1.25rem;flex-wrap:wrap;margin-top:.75rem;font-size:.8125rem;" class="sp-mute">
                <span><strong class="sp-good">{{ $att['present'] }}</strong> present</span>
                <span><strong class="sp-warn">{{ $att['late'] }}</strong> late</span>
                <span><strong class="sp-bad">{{ $att['absent'] }}</strong> absent</span>
                <span><strong>{{ $att['leave'] }}</strong> excused</span>
            </div>
        @else
            <p class="sp-empty">No attendance recorded yet.</p>
        @endif
    </x-filament::section>

    {{-- ── One block per exam ──────────────────────────────────────── --}}
    @forelse ($this->results as $result)
        @php
            $marks = $this->marksByExam[$result->exam_id] ?? collect();
            $pct = $result->percentage !== null ? round((float) $result->percentage, 1) : null;
            $passing = $pct !== null && $pct >= 60;
        @endphp

        <x-filament::section>
            <x-slot name="heading">{{ $result->exam?->name ?? 'Exam' }}</x-slot>
            <x-slot name="description">
                {{ $marks->count() }} {{ Str::plural('subject', $marks->count()) }}
                @if ($result->rank) · class rank {{ $result->rank }} @endif
            </x-slot>

            {{-- headline figures --}}
            <div class="sp-grid-stats" style="margin-bottom:1rem;">
                <div class="sp-stat">
                    <div class="sp-stat__value {{ $passing ? 'sp-good' : 'sp-bad' }}">{{ $result->grade ?? '—' }}</div>
                    <div class="sp-stat__label">Grade</div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value sp-ink">{{ $pct !== null ? $pct . '%' : '—' }}</div>
                    <div class="sp-stat__label">Overall</div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value sp-ink">
                        {{ $result->marks_obtained !== null ? (int) $result->marks_obtained : '—' }}<span class="sp-mute" style="font-size:1rem;">/{{ $result->total_marks !== null ? (int) $result->total_marks : '—' }}</span>
                    </div>
                    <div class="sp-stat__label">Marks</div>
                </div>
                <div class="sp-stat">
                    <div class="sp-stat__value sp-teal">{{ $result->grade_point !== null ? number_format((float) $result->grade_point, 1) : '—' }}</div>
                    <div class="sp-stat__label">Grade point</div>
                </div>
            </div>

            {{-- per-subject breakdown --}}
            @if ($marks->isNotEmpty())
                <div class="sp-table__scroll">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th class="sp-num">Marks</th>
                                <th class="sp-num">Out of</th>
                                <th class="sp-num">%</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($marks->sortBy(fn ($m) => $m->schedule?->subject?->name ?? '') as $mark)
                                @php
                                    $full = $mark->schedule?->full_marks;
                                    $got  = $mark->is_absent ? null : $mark->marks_obtained;
                                    $mp   = ($got !== null && $full) ? round($got / $full * 100, 1) : null;
                                @endphp
                                <tr>
                                    <td>{{ $mark->schedule?->subject?->name ?? 'Subject' }}</td>
                                    <td class="sp-num">{{ $mark->is_absent ? '—' : ($got !== null ? (int) $got : '—') }}</td>
                                    <td class="sp-num">{{ $full ? (int) $full : '—' }}</td>
                                    <td class="sp-num">{{ $mp !== null ? $mp . '%' : '—' }}</td>
                                    <td>
                                        @if ($mark->is_absent)
                                            <span class="sp-badge sp-badge--late">Absent</span>
                                        @elseif ($mp !== null)
                                            <span class="sp-badge {{ $mp >= 60 ? 'sp-badge--ok' : 'sp-badge--late' }}">{{ $mp >= 60 ? 'Pass' : 'Below pass' }}</span>
                                        @else
                                            <span class="sp-badge sp-badge--due">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (filled($result->remarks))
                <p class="sp-row__meta" style="margin-top:.75rem;"><strong>Remarks:</strong> {{ $result->remarks }}</p>
            @endif
        </x-filament::section>
    @empty
        <x-filament::section>
            <p class="sp-empty">No exam results published yet.</p>
        </x-filament::section>
    @endforelse
</x-filament-panels::page>
