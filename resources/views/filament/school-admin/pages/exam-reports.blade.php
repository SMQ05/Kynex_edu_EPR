@php
    use Illuminate\Support\Str;
@endphp
<x-filament-panels::page>
    <div class="space-y-6">
        {{-- ── Filters ─────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white">Report Type</label>
                        <select wire:model.live="report_type"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="tabulation">Tabulation Sheet</option>
                            <option value="marksheet">Mark Sheet (per student)</option>
                            <option value="subject_wise">Subject-wise Marksheet</option>
                            <option value="merit_list">Merit List</option>
                            <option value="progress_card">Progress Card</option>
                            <option value="previous_result">Previous Result</option>
                            <option value="exam_routine">Exam Routine</option>
                        </select>
                    </div>

                    @if ($report_type !== 'previous_result')
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Exam</label>
                            <select wire:model.live="exam_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">Select Exam</option>
                                @foreach ($this->exams as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white">Class</label>
                        <select wire:model.live="class_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Select Class</option>
                            @foreach ($this->classes as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white">Section</label>
                        <select wire:model.live="section_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">All Sections</option>
                            @foreach ($this->sections as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($report_type === 'subject_wise')
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Subject</label>
                            <select wire:model.live="subject_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">Select Subject</option>
                                @foreach ($this->subjects as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if (in_array($report_type, ['marksheet', 'progress_card']))
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Student</label>
                            <select wire:model.live="student_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">Select Student</option>
                                @foreach ($this->studentsForClass as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($report_type === 'previous_result')
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Academic Year</label>
                            <select wire:model.live="academic_year_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">All Years</option>
                                @foreach ($this->academicYears as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <x-filament::button wire:click="generateReport" icon="heroicon-o-chart-bar">
                        Generate Report
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- ── AI summary ──────────────────────────────────────── --}}
        @if (filled($aiSummary))
            <div class="fi-section rounded-xl bg-primary-50 p-5 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/20">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-sparkles class="h-5 w-5 text-primary-600" />
                    <h3 class="text-sm font-semibold text-primary-700 dark:text-primary-400">AI Executive Summary</h3>
                </div>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $aiSummary }}</p>
            </div>
        @endif

        {{-- ── Results ─────────────────────────────────────────── --}}
        @if ($isLoaded)
            @php $d = $reportData; @endphp

            @if (! empty($d['heading']))
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $d['heading'] }}</h2>
            @endif

            {{-- Tabulation Sheet --}}
            @if ($report_type === 'tabulation' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-3 py-2 text-start font-semibold">Roll</th>
                                <th class="px-3 py-2 text-start font-semibold">Student</th>
                                @foreach ($d['subjects'] as $subject)
                                    <th class="px-3 py-2 text-center font-semibold">{{ $subject['name'] }}<br><span class="text-xs text-gray-400">/{{ $subject['full_marks'] }}</span></th>
                                @endforeach
                                <th class="px-3 py-2 text-center font-semibold">Total</th>
                                <th class="px-3 py-2 text-center font-semibold">%</th>
                                <th class="px-3 py-2 text-center font-semibold">Grade</th>
                                <th class="px-3 py-2 text-center font-semibold">Rank</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-3 py-2">{{ $row['roll_number'] ?? '—' }}</td>
                                    <td class="px-3 py-2 font-medium">{{ $row['name'] }}</td>
                                    @foreach ($row['cells'] as $cell)
                                        <td @class([
                                            'px-3 py-2 text-center',
                                            'text-danger-600 font-semibold' => $cell['is_absent'] || $cell['is_pass'] === false,
                                            'text-success-700' => $cell['is_pass'] === true,
                                        ])>
                                            {{ $cell['is_absent'] ? 'Abs' : ($cell['value'] ?? '—') }}
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 text-center font-semibold">{{ $row['obtained'] }}/{{ $row['total'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['percentage'] }}%</td>
                                    <td class="px-3 py-2 text-center">{{ $row['grade'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['rank'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Mark Sheet / Progress Card (per student) --}}
            @if (in_array($report_type, ['marksheet', 'progress_card']) && ! empty($d['card']))
                @php $card = $d['card']; @endphp
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-header px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ $card['student']->full_name ?? '—' }}
                            @if (! empty($card['student']->admission_number)) ({{ $card['student']->admission_number }}) @endif
                        </h3>
                    </div>
                    <div class="overflow-x-auto border-t border-gray-200 dark:border-white/10">
                        <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-2 text-start font-semibold">Subject</th>
                                    <th class="px-4 py-2 text-center font-semibold">Full</th>
                                    <th class="px-4 py-2 text-center font-semibold">Pass</th>
                                    <th class="px-4 py-2 text-center font-semibold">Obtained</th>
                                    <th class="px-4 py-2 text-center font-semibold">Result</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($card['subjects'] as $subject)
                                    <tr>
                                        <td class="px-4 py-2">{{ $subject['subject'] }}</td>
                                        <td class="px-4 py-2 text-center">{{ $subject['full_marks'] }}</td>
                                        <td class="px-4 py-2 text-center">{{ $subject['pass_marks'] }}</td>
                                        <td class="px-4 py-2 text-center font-medium">{{ $subject['is_absent'] ? 'Absent' : ($subject['marks_obtained'] ?? '—') }}</td>
                                        <td @class([
                                            'px-4 py-2 text-center font-semibold',
                                            'text-success-700' => $subject['is_pass'],
                                            'text-danger-600' => ! $subject['is_pass'],
                                        ])>{{ $subject['is_pass'] ? 'Pass' : 'Fail' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-white/5">
                                <tr class="font-semibold">
                                    <td class="px-4 py-2">Total</td>
                                    <td class="px-4 py-2 text-center">{{ $card['total_marks'] }}</td>
                                    <td></td>
                                    <td class="px-4 py-2 text-center">{{ $card['obtained'] }}</td>
                                    <td class="px-4 py-2 text-center">{{ $card['percentage'] }}%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-t border-gray-200 p-4 sm:grid-cols-4 dark:border-white/10">
                        <div><p class="text-xs text-gray-500">Grade</p><p class="text-lg font-bold">{{ $card['grade'] ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Grade Point</p><p class="text-lg font-bold">{{ $card['grade_point'] ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Rank</p><p class="text-lg font-bold">{{ $card['rank'] ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Status</p><p class="text-lg font-bold">{{ $card['status']?->label() ?? '—' }}</p></div>
                    </div>
                    @if ($report_type === 'progress_card' && ! empty($d['classSummary']))
                        <div class="border-t border-gray-200 p-4 text-sm text-gray-600 dark:border-white/10 dark:text-gray-400">
                            Class average: <strong>{{ $d['classSummary']['average'] }}%</strong> ·
                            Highest: <strong>{{ $d['classSummary']['highest'] }}%</strong> ·
                            Pass rate: <strong>{{ $d['classSummary']['pass_rate'] }}%</strong>
                            ({{ $d['classSummary']['total_students'] }} students)
                        </div>
                    @endif
                </div>
            @endif

            {{-- Subject-wise Marksheet --}}
            @if ($report_type === 'subject_wise' && ! empty($d['rows']))
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Appeared</p><p class="text-xl font-bold">{{ $d['stats']['appeared'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-success-600">Passed</p><p class="text-xl font-bold text-success-600">{{ $d['stats']['passed'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Highest</p><p class="text-xl font-bold">{{ $d['stats']['highest'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Lowest</p><p class="text-xl font-bold">{{ $d['stats']['lowest'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Average</p><p class="text-xl font-bold">{{ $d['stats']['average'] }}</p></div>
                </div>
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-start font-semibold">Roll</th>
                                <th class="px-4 py-2 text-start font-semibold">Student</th>
                                <th class="px-4 py-2 text-center font-semibold">{{ $d['subject'] }} (/{{ $d['full_marks'] }})</th>
                                <th class="px-4 py-2 text-center font-semibold">Result</th>
                                <th class="px-4 py-2 text-start font-semibold">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['roll_number'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['is_absent'] ? 'Absent' : ($row['marks'] ?? '—') }}</td>
                                    <td @class(['px-4 py-2 text-center font-semibold', 'text-success-700' => $row['is_pass'] === true, 'text-danger-600' => $row['is_pass'] === false])>
                                        {{ $row['is_pass'] === null ? '—' : ($row['is_pass'] ? 'Pass' : 'Fail') }}
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $row['remarks'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Merit List --}}
            @if ($report_type === 'merit_list' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-center font-semibold">Position</th>
                                <th class="px-4 py-2 text-start font-semibold">Student</th>
                                <th class="px-4 py-2 text-start font-semibold">Admission</th>
                                <th class="px-4 py-2 text-center font-semibold">Marks</th>
                                <th class="px-4 py-2 text-center font-semibold">%</th>
                                <th class="px-4 py-2 text-center font-semibold">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr @class(['bg-warning-50 dark:bg-warning-500/10' => $row['position'] <= 3])>
                                    <td class="px-4 py-2 text-center font-bold">{{ $row['position'] }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $row['admission'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['obtained'] }}/{{ $row['total'] }}</td>
                                    <td class="px-4 py-2 text-center font-semibold">{{ $row['percentage'] }}%</td>
                                    <td class="px-4 py-2 text-center">{{ $row['grade'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Previous Result (AnnualResult) --}}
            @if ($report_type === 'previous_result' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-center font-semibold">Rank</th>
                                <th class="px-4 py-2 text-start font-semibold">Student</th>
                                <th class="px-4 py-2 text-start font-semibold">Admission</th>
                                <th class="px-4 py-2 text-start font-semibold">Year</th>
                                <th class="px-4 py-2 text-center font-semibold">Final %</th>
                                <th class="px-4 py-2 text-center font-semibold">Grade</th>
                                <th class="px-4 py-2 text-center font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2 text-center">{{ $row['rank'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $row['admission'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row['year'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center font-semibold">{{ $row['percentage'] ?? '—' }}%</td>
                                    <td class="px-4 py-2 text-center">{{ $row['grade'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ Str::title($row['status'] ?? '—') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Exam Routine --}}
            @if ($report_type === 'exam_routine' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-start font-semibold">Date</th>
                                <th class="px-4 py-2 text-center font-semibold">Time</th>
                                <th class="px-4 py-2 text-start font-semibold">Class</th>
                                <th class="px-4 py-2 text-start font-semibold">Section</th>
                                <th class="px-4 py-2 text-start font-semibold">Subject</th>
                                <th class="px-4 py-2 text-start font-semibold">Room</th>
                                <th class="px-4 py-2 text-center font-semibold">Full Marks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['date'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['start'] ?? '—' }}@if($row['end']) – {{ $row['end'] }}@endif</td>
                                    <td class="px-4 py-2">{{ $row['class'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row['section'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['subject'] }}</td>
                                    <td class="px-4 py-2">{{ $row['room'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['full_marks'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @unless ($this->hasData())
                <div class="fi-section rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No data found for the selected criteria. Make sure marks/results have been entered for this exam.</p>
                </div>
            @endunless
        @endif
    </div>
</x-filament-panels::page>
