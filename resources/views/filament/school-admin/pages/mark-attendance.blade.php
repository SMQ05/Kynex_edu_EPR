<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white">Class</label>
                        <select wire:model.live="class_id"
                                class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Select Class</option>
                            @foreach ($this->classes as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white">Section</label>
                        <select wire:model.live="section_id"
                                class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Select Section</option>
                            @foreach ($this->sections as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white">Date</label>
                        <input type="date"
                               wire:model.live="date"
                               class="fi-input mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>

                    <div class="flex items-end">
                        <x-filament::button wire:click="loadStudents" icon="heroicon-o-magnifying-glass">
                            Load Students
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance Table --}}
        @if ($isLoaded && count($students) > 0)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
                    <h3 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white">
                        Student Attendance ({{ count($students) }} students)
                    </h3>
                    <div class="ml-auto flex gap-2">
                        <x-filament::button wire:click="markAllPresent" color="success" size="sm" outlined>
                            All Present
                        </x-filament::button>
                        <x-filament::button wire:click="markAllAbsent" color="danger" size="sm" outlined>
                            All Absent
                        </x-filament::button>
                    </div>
                </div>

                <div class="fi-section-content border-t border-gray-200 dark:border-white/10">
                    <div class="overflow-x-auto">
                        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr>
                                    <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                                    <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Roll No</th>
                                    <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Student Name</th>
                                    <th class="fi-ta-header-cell px-4 py-3 text-center text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                                    <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach ($students as $index => $student)
                                    <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5"
                                        wire:key="student-{{ $student['student_id'] }}">
                                        <td class="fi-ta-cell px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="fi-ta-cell px-4 py-3 text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $student['roll_number'] ?? '—' }}
                                        </td>
                                        <td class="fi-ta-cell px-4 py-3 text-sm text-gray-950 dark:text-white">
                                            {{ $student['student_name'] }}
                                        </td>
                                        <td class="fi-ta-cell px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                @foreach (\App\Enums\AttendanceStatus::cases() as $status)
                                                    @if ($status->value !== 'holiday')
                                                        <button
                                                            wire:click="$set('attendance.{{ $student['student_id'] }}.status', '{{ $status->value }}')"
                                                            @class([
                                                                'inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold transition-colors',
                                                                'bg-success-100 text-success-700 ring-1 ring-success-600/20 dark:bg-success-500/20 dark:text-success-400' =>
                                                                    ($attendance[$student['student_id']]['status'] ?? '') === $status->value && $status->value === 'present',
                                                                'bg-danger-100 text-danger-700 ring-1 ring-danger-600/20 dark:bg-danger-500/20 dark:text-danger-400' =>
                                                                    ($attendance[$student['student_id']]['status'] ?? '') === $status->value && $status->value === 'absent',
                                                                'bg-warning-100 text-warning-700 ring-1 ring-warning-600/20 dark:bg-warning-500/20 dark:text-warning-400' =>
                                                                    ($attendance[$student['student_id']]['status'] ?? '') === $status->value && $status->value === 'late',
                                                                'bg-info-100 text-info-700 ring-1 ring-info-600/20 dark:bg-info-500/20 dark:text-info-400' =>
                                                                    ($attendance[$student['student_id']]['status'] ?? '') === $status->value && in_array($status->value, ['half_day', 'excused']),
                                                                'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' =>
                                                                    ($attendance[$student['student_id']]['status'] ?? '') !== $status->value,
                                                            ])
                                                        >
                                                            {{ $status->shortLabel() }}
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="fi-ta-cell px-4 py-3">
                                            <input type="text"
                                                   wire:model.blur="attendance.{{ $student['student_id'] }}.remarks"
                                                   placeholder="Optional"
                                                   class="fi-input w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Save Button --}}
                <div class="fi-section-footer flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        @php
                            $statusCounts = collect($attendance)->countBy('status');
                        @endphp
                        <span class="text-success-600">P: {{ $statusCounts->get('present', 0) }}</span>
                        <span class="mx-1">|</span>
                        <span class="text-danger-600">A: {{ $statusCounts->get('absent', 0) }}</span>
                        <span class="mx-1">|</span>
                        <span class="text-warning-600">L: {{ $statusCounts->get('late', 0) }}</span>
                        <span class="mx-1">|</span>
                        <span class="text-info-600">HD: {{ $statusCounts->get('half_day', 0) }}</span>
                        <span class="mx-1">|</span>
                        <span class="text-primary-600">E: {{ $statusCounts->get('excused', 0) }}</span>
                    </div>
                    <x-filament::button wire:click="saveAttendance" icon="heroicon-o-check-circle" size="lg">
                        Save Attendance
                    </x-filament::button>
                </div>
            </div>
        @elseif ($isLoaded)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No students found for the selected class and section.
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
