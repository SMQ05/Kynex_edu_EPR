<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white">Report Type</label>
                        <select wire:model.live="report_type"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="class_summary">Class Summary</option>
                            <option value="student_detail">Student Detail</option>
                            <option value="daily">Daily Report</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white">Class</label>
                        <select wire:model.live="class_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Select Class</option>
                            @foreach (\App\Models\Tenant\SchoolClass::orderBy('name')->pluck('name', 'id') as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-950 dark:text-white">Section</label>
                        <select wire:model.live="section_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Select Section</option>
                            @foreach ($this->sections as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($report_type === 'student_detail')
                        <div>
                            <label class="text-sm font-medium text-gray-950 dark:text-white">Student</label>
                            <select wire:model.live="student_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">Select Student</option>
                                @foreach ($this->students as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

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
                </div>

                <div class="mt-4">
                    <x-filament::button wire:click="generateReport" icon="heroicon-o-chart-bar">
                        Generate Report
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Report Results --}}
        @if ($isLoaded)
            {{-- Class Summary --}}
            @if ($report_type === 'class_summary' && count($reportData) > 0)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-header px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            Class Attendance Summary ({{ $date_from }} to {{ $date_to }})
                        </h3>
                    </div>
                    <div class="overflow-x-auto border-t border-gray-200 dark:border-white/10">
                        <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Roll No</th>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Student</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Days</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-success-600">P</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-danger-600">A</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-warning-600">L</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-info-600">HD</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-primary-600">E</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($reportData as $index => $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">{{ $row['roll_number'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $row['student_name'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm">{{ $row['total_days'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-success-600 font-semibold">{{ $row['present'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-danger-600 font-semibold">{{ $row['absent'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-warning-600 font-semibold">{{ $row['late'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-info-600 font-semibold">{{ $row['half_day'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-primary-600 font-semibold">{{ $row['excused'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-bold">
                                            <span @class([
                                                'text-success-600' => $row['attendance_percentage'] >= 75,
                                                'text-warning-600' => $row['attendance_percentage'] >= 50 && $row['attendance_percentage'] < 75,
                                                'text-danger-600'  => $row['attendance_percentage'] < 50,
                                            ])>
                                                {{ $row['attendance_percentage'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Student Detail --}}
            @if ($report_type === 'student_detail' && ! empty($reportData))
                <div class="space-y-4">
                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Days</p>
                            <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $reportData['summary']['total_days'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs text-success-600">Present</p>
                            <p class="text-2xl font-bold text-success-600">{{ $reportData['summary']['present'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs text-danger-600">Absent</p>
                            <p class="text-2xl font-bold text-danger-600">{{ $reportData['summary']['absent'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs text-warning-600">Late</p>
                            <p class="text-2xl font-bold text-warning-600">{{ $reportData['summary']['late'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs text-info-600">Half Day</p>
                            <p class="text-2xl font-bold text-info-600">{{ $reportData['summary']['half_day'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs text-primary-600">Excused</p>
                            <p class="text-2xl font-bold text-primary-600">{{ $reportData['summary']['excused'] }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Percentage</p>
                            <p class="text-2xl font-bold" @class([
                                'text-success-600' => $reportData['summary']['attendance_percentage'] >= 75,
                                'text-warning-600' => $reportData['summary']['attendance_percentage'] >= 50 && $reportData['summary']['attendance_percentage'] < 75,
                                'text-danger-600'  => $reportData['summary']['attendance_percentage'] < 50,
                            ])>{{ $reportData['summary']['attendance_percentage'] }}%</p>
                        </div>
                    </div>

                    {{-- Day-by-day records --}}
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="fi-section-header px-6 py-4">
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                                {{ $reportData['student']['name'] }} ({{ $reportData['student']['roll_number'] }}) — Day-by-Day
                            </h3>
                        </div>
                        <div class="overflow-x-auto border-t border-gray-200 dark:border-white/10">
                            <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                                <thead class="bg-gray-50 dark:bg-white/5">
                                    <tr>
                                        <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Date</th>
                                        <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Day</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                                        <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                    @foreach ($reportData['records'] as $record)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                            <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $record['date'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $record['day'] }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span @class([
                                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                    'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $record['status_color'] === 'success',
                                                    'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-400' => $record['status_color'] === 'danger',
                                                    'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $record['status_color'] === 'warning',
                                                    'bg-info-100 text-info-700 dark:bg-info-500/20 dark:text-info-400' => $record['status_color'] === 'info',
                                                    'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-400' => $record['status_color'] === 'primary',
                                                    'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => $record['status_color'] === 'gray',
                                                ])>
                                                    {{ $record['status_label'] }}
                                                    @if ($record['late_minutes'])
                                                        ({{ $record['late_minutes'] }}m)
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $record['remarks'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Daily Report --}}
            @if ($report_type === 'daily' && count($reportData) > 0)
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-header px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            Daily Attendance Summary
                        </h3>
                    </div>
                    <div class="overflow-x-auto border-t border-gray-200 dark:border-white/10">
                        <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Date</th>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Day</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Total</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-success-600">Present</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-danger-600">Absent</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-warning-600">Late</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-info-600">Half Day</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-primary-600">Excused</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($reportData as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 text-sm text-gray-950 dark:text-white">{{ $row['date'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $row['day'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-semibold">{{ $row['total'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-success-600 font-semibold">{{ $row['statuses']['present'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-danger-600 font-semibold">{{ $row['statuses']['absent'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-warning-600 font-semibold">{{ $row['statuses']['late'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-info-600 font-semibold">{{ $row['statuses']['half_day'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-primary-600 font-semibold">{{ $row['statuses']['excused'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($isLoaded && empty($reportData))
                <div class="fi-section rounded-xl bg-white p-6 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No attendance data found for the selected criteria.</p>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
