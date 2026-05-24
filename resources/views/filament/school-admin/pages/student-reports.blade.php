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
                            <option value="subject_attendance">Subject Attendance Report</option>
                            <option value="homework_eval">Homework Evaluation Report</option>
                            <option value="student_history">Student History</option>
                            <option value="transport">Student Transport Report</option>
                            <option value="dormitory">Student Dormitory Report</option>
                            <option value="guardian">Guardian Report</option>
                        </select>
                    </div>

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

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white">
                            Student @if ($report_type === 'student_history') <span class="text-danger-500">*</span> @else (optional) @endif
                        </label>
                        <select wire:model.live="student_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">{{ $report_type === 'student_history' ? 'Select Student' : 'All Students' }}</option>
                            @foreach ($this->studentsForClass as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (in_array($report_type, ['subject_attendance', 'homework_eval']))
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">From Date</label>
                            <input type="date" wire:model.live="date_from"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">To Date</label>
                            <input type="date" wire:model.live="date_to"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
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

            {{-- Subject Attendance --}}
            @if ($report_type === 'subject_attendance' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-start font-semibold">Roll</th>
                                <th class="px-4 py-2 text-start font-semibold">Student</th>
                                <th class="px-4 py-2 text-start font-semibold">Subject</th>
                                <th class="px-4 py-2 text-center font-semibold">Total</th>
                                <th class="px-4 py-2 text-center font-semibold text-success-600">Present</th>
                                <th class="px-4 py-2 text-center font-semibold text-danger-600">Absent</th>
                                <th class="px-4 py-2 text-center font-semibold text-warning-600">Late</th>
                                <th class="px-4 py-2 text-center font-semibold">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['roll'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['student'] }}</td>
                                    <td class="px-4 py-2">{{ $row['subject'] }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['total'] }}</td>
                                    <td class="px-4 py-2 text-center text-success-600 font-semibold">{{ $row['present'] }}</td>
                                    <td class="px-4 py-2 text-center text-danger-600 font-semibold">{{ $row['absent'] }}</td>
                                    <td class="px-4 py-2 text-center text-warning-600 font-semibold">{{ $row['late'] }}</td>
                                    <td @class(['px-4 py-2 text-center font-bold', 'text-success-600' => $row['percentage'] >= 75, 'text-warning-600' => $row['percentage'] >= 50 && $row['percentage'] < 75, 'text-danger-600' => $row['percentage'] < 50])>{{ $row['percentage'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Homework Evaluation --}}
            @if ($report_type === 'homework_eval' && ! empty($d['rows']))
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Submissions</p><p class="text-xl font-bold">{{ $d['stats']['total'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-success-600">Graded</p><p class="text-xl font-bold text-success-600">{{ $d['stats']['graded'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-warning-600">Pending</p><p class="text-xl font-bold text-warning-600">{{ $d['stats']['pending'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Avg Score</p><p class="text-xl font-bold">{{ $d['stats']['avg_pct'] !== null ? $d['stats']['avg_pct'].'%' : '—' }}</p></div>
                </div>
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-start font-semibold">Roll</th>
                                <th class="px-4 py-2 text-start font-semibold">Student</th>
                                <th class="px-4 py-2 text-start font-semibold">Homework</th>
                                <th class="px-4 py-2 text-start font-semibold">Subject</th>
                                <th class="px-4 py-2 text-start font-semibold">Submitted</th>
                                <th class="px-4 py-2 text-center font-semibold">Marks</th>
                                <th class="px-4 py-2 text-center font-semibold">Grade</th>
                                <th class="px-4 py-2 text-center font-semibold">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['roll'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['student'] }}</td>
                                    <td class="px-4 py-2">{{ $row['homework'] }}</td>
                                    <td class="px-4 py-2">{{ $row['subject'] }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $row['submitted_at'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['marks'] !== null ? $row['marks'].'/'.($row['total_marks'] ?? '?') : '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['grade'] ?? ($row['graded'] ? '—' : 'Pending') }}</td>
                                    <td class="px-4 py-2 text-center font-semibold">{{ $row['percentage'] !== null ? $row['percentage'].'%' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Student History --}}
            @if ($report_type === 'student_history' && ! empty($d['student']))
                @php $s = $d['student']; @endphp
                <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $s->full_name }}</h3>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                        <div><p class="text-xs text-gray-500">Admission No</p><p class="font-medium">{{ $s->admission_number ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Roll No</p><p class="font-medium">{{ $s->roll_number ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Class</p><p class="font-medium">{{ $s->schoolClass?->name ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Section</p><p class="font-medium">{{ $s->section?->name ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Category</p><p class="font-medium">{{ $s->category?->name ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Admission Date</p><p class="font-medium">{{ $s->admission_date?->format('d M Y') ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Date of Birth</p><p class="font-medium">{{ $s->date_of_birth?->format('d M Y') ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Status</p><p class="font-medium">{{ Str::title(is_object($s->status) ? $s->status->value : ($s->status ?? '—')) }}</p></div>
                        <div><p class="text-xs text-gray-500">Phone</p><p class="font-medium">{{ $s->phone ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">Previous School</p><p class="font-medium">{{ $s->previous_school ?? '—' }}</p></div>
                    </div>
                </div>

                @if ($d['guardians']->isNotEmpty())
                    <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="px-6 py-3"><h4 class="text-sm font-semibold">Guardians</h4></div>
                        <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5"><tr>
                                <th class="px-4 py-2 text-start font-semibold">Name</th>
                                <th class="px-4 py-2 text-start font-semibold">Relationship</th>
                                <th class="px-4 py-2 text-start font-semibold">Phone</th>
                                <th class="px-4 py-2 text-start font-semibold">Email</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($d['guardians'] as $g)
                                    <tr>
                                        <td class="px-4 py-2 font-medium">{{ $g->name }}</td>
                                        <td class="px-4 py-2">{{ $g->relationship ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $g->phone ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $g->email ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($d['promotions']->isNotEmpty())
                    <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="px-6 py-3"><h4 class="text-sm font-semibold">Promotion History</h4></div>
                        <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5"><tr>
                                <th class="px-4 py-2 text-start font-semibold">Date</th>
                                <th class="px-4 py-2 text-start font-semibold">From</th>
                                <th class="px-4 py-2 text-start font-semibold">To</th>
                                <th class="px-4 py-2 text-start font-semibold">Year</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($d['promotions'] as $p)
                                    <tr>
                                        <td class="px-4 py-2">{{ $p->promoted_at?->format('d M Y') ?? $p->created_at?->format('d M Y') }}</td>
                                        <td class="px-4 py-2">{{ $p->fromClass?->name ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $p->toClass?->name ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $p->toAcademicYear?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif

            {{-- Transport --}}
            @if ($report_type === 'transport' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5"><tr>
                            <th class="px-4 py-2 text-start font-semibold">Roll</th>
                            <th class="px-4 py-2 text-start font-semibold">Student</th>
                            <th class="px-4 py-2 text-start font-semibold">Route</th>
                            <th class="px-4 py-2 text-start font-semibold">Stop</th>
                            <th class="px-4 py-2 text-start font-semibold">Direction</th>
                            <th class="px-4 py-2 text-center font-semibold">Active</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['roll'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['student'] }}</td>
                                    <td class="px-4 py-2">{{ $row['route'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row['stop'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ Str::title($row['direction'] ?? '—') }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['active'] ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Dormitory --}}
            @if ($report_type === 'dormitory' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5"><tr>
                            <th class="px-4 py-2 text-start font-semibold">Roll</th>
                            <th class="px-4 py-2 text-start font-semibold">Student</th>
                            <th class="px-4 py-2 text-start font-semibold">Building</th>
                            <th class="px-4 py-2 text-start font-semibold">Room</th>
                            <th class="px-4 py-2 text-center font-semibold">Bed</th>
                            <th class="px-4 py-2 text-start font-semibold">Status</th>
                            <th class="px-4 py-2 text-start font-semibold">Check-in</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['roll'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['student'] }}</td>
                                    <td class="px-4 py-2">{{ $row['building'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row['room'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['bed'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ Str::title($row['status'] ?? '—') }}</td>
                                    <td class="px-4 py-2">{{ $row['check_in'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Guardian --}}
            @if ($report_type === 'guardian' && ! empty($d['rows']))
                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5"><tr>
                            <th class="px-4 py-2 text-start font-semibold">Roll</th>
                            <th class="px-4 py-2 text-start font-semibold">Student</th>
                            <th class="px-4 py-2 text-start font-semibold">Guardian</th>
                            <th class="px-4 py-2 text-start font-semibold">Relationship</th>
                            <th class="px-4 py-2 text-start font-semibold">Phone</th>
                            <th class="px-4 py-2 text-start font-semibold">Email</th>
                            <th class="px-4 py-2 text-center font-semibold">Primary</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['roll'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['student'] }}</td>
                                    <td class="px-4 py-2">{{ $row['guardian'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row['relationship'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row['phone'] ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $row['email'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['is_primary'] ? 'Yes' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @unless ($this->hasData())
                <div class="fi-section rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No data found for the selected criteria.</p>
                </div>
            @endunless
        @endif
    </div>
</x-filament-panels::page>
