<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exam</label>
                    <select wire:model.live="exam_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                        <option value="">-- Select Exam --</option>
                        @foreach($this->exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                    <select wire:model.live="class_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                        <option value="">-- Select Class --</option>
                        @foreach($this->classes as $cls)
                            <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">View</label>
                    <select wire:model.live="view_mode" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                        <option value="class_results">Class Results</option>
                        <option value="merit_list">Merit List</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        @if($exam_id && $class_id && !empty($this->classSummary) && $view_mode !== 'report_card')
            @php $summary = $this->classSummary; @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 text-center">
                    <div class="text-2xl font-bold text-primary-600">{{ $summary['total_students'] }}</div>
                    <div class="text-sm text-gray-500">Total Students</div>
                </div>
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $summary['pass_count'] }}</div>
                    <div class="text-sm text-gray-500">Passed ({{ $summary['pass_rate'] }}%)</div>
                </div>
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $summary['fail_count'] }}</div>
                    <div class="text-sm text-gray-500">Failed</div>
                </div>
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ number_format($summary['average'], 1) }}%</div>
                    <div class="text-sm text-gray-500">Class Average</div>
                </div>
            </div>
        @endif

        {{-- Class Results Table --}}
        @if($view_mode === 'class_results' && $this->results->isNotEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">Class Results</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Rank</th>
                                <th class="px-4 py-3 font-medium">Student</th>
                                <th class="px-4 py-3 font-medium text-right">Total</th>
                                <th class="px-4 py-3 font-medium text-right">Obtained</th>
                                <th class="px-4 py-3 font-medium text-right">%</th>
                                <th class="px-4 py-3 font-medium">Grade</th>
                                <th class="px-4 py-3 font-medium">GPA</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->results as $result)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-2 font-mono">{{ $result->rank ?? '-' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $result->student?->full_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-right">{{ $result->total_marks }}</td>
                                    <td class="px-4 py-2 text-right">{{ $result->marks_obtained }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($result->percentage, 1) }}%</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                            {{ $result->grade ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $result->grade_point ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        @php
                                            $statusColor = match($result->status?->value ?? '') {
                                                'pass' => 'green',
                                                'fail' => 'red',
                                                'absent' => 'gray',
                                                'withheld' => 'yellow',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">
                                            {{ $result->status?->label() ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <button wire:click="viewReportCard('{{ $result->student_id }}')" class="text-primary-600 hover:text-primary-800 text-xs underline">
                                            Report Card
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Merit List --}}
        @if($view_mode === 'merit_list' && $this->meritList->isNotEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-4">🏆 Merit List (Top 20)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">Rank</th>
                                <th class="px-4 py-3 font-medium">Student</th>
                                <th class="px-4 py-3 font-medium text-right">Marks</th>
                                <th class="px-4 py-3 font-medium text-right">Percentage</th>
                                <th class="px-4 py-3 font-medium">Grade</th>
                                <th class="px-4 py-3 font-medium">GPA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->meritList as $result)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $result->rank <= 3 ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                                    <td class="px-4 py-2 font-bold text-lg">
                                        @if($result->rank === 1) 🥇
                                        @elseif($result->rank === 2) 🥈
                                        @elseif($result->rank === 3) 🥉
                                        @else {{ $result->rank }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 font-medium">{{ $result->student?->full_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-right">{{ $result->marks_obtained }} / {{ $result->total_marks }}</td>
                                    <td class="px-4 py-2 text-right font-semibold">{{ number_format($result->percentage, 1) }}%</td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-700">
                                            {{ $result->grade ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">{{ $result->grade_point ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Report Card --}}
        @if($view_mode === 'report_card' && !empty($this->reportCard))
            @php $card = $this->reportCard; @endphp
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        Report Card — {{ $card['student']?->full_name ?? 'N/A' }}
                    </h3>
                    <x-filament::button wire:click="backToResults" color="gray" size="sm">
                        ← Back to Results
                    </x-filament::button>
                </div>

                {{-- Student Info --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div>
                        <span class="text-xs text-gray-500">Roll Number</span>
                        <div class="font-medium">{{ $card['student']?->roll_number ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500">Class</span>
                        <div class="font-medium">{{ $card['student']?->schoolClass?->name ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500">Overall Grade</span>
                        <div class="font-bold text-primary-600">{{ $card['grade'] ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500">Rank</span>
                        <div class="font-bold">{{ $card['rank'] ?? '-' }}</div>
                    </div>
                </div>

                {{-- Subject-wise marks --}}
                <table class="w-full text-sm text-left mb-6">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 font-medium">Subject</th>
                            <th class="px-4 py-3 font-medium text-right">Full Marks</th>
                            <th class="px-4 py-3 font-medium text-right">Pass Marks</th>
                            <th class="px-4 py-3 font-medium text-right">Obtained</th>
                            <th class="px-4 py-3 font-medium">Result</th>
                            <th class="px-4 py-3 font-medium">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($card['subjects'] as $subj)
                            <tr class="{{ !$subj['is_pass'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                <td class="px-4 py-2 font-medium">{{ $subj['subject'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $subj['full_marks'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $subj['pass_marks'] }}</td>
                                <td class="px-4 py-2 text-right font-semibold">
                                    @if($subj['is_absent'])
                                        <span class="text-red-600">Absent</span>
                                    @else
                                        {{ $subj['marks_obtained'] ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @if($subj['is_absent'])
                                        <span class="text-red-600 text-xs">Absent</span>
                                    @elseif($subj['is_pass'])
                                        <span class="text-green-600 text-xs font-medium">Pass</span>
                                    @else
                                        <span class="text-red-600 text-xs font-medium">Fail</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-500 text-xs">{{ $subj['remarks'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-700 font-semibold">
                        <tr>
                            <td class="px-4 py-2">Total</td>
                            <td class="px-4 py-2 text-right">{{ $card['total_marks'] }}</td>
                            <td class="px-4 py-2"></td>
                            <td class="px-4 py-2 text-right">{{ $card['obtained'] }}</td>
                            <td class="px-4 py-2" colspan="2">
                                {{ number_format($card['percentage'], 1) }}%
                                — Grade: {{ $card['grade'] ?? '-' }}
                                (GPA: {{ $card['grade_point'] ?? '-' }})
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        {{-- Empty State --}}
        @if(!$exam_id || !$class_id)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center">
                <div class="text-gray-400 text-lg">Select an exam and class to view results</div>
            </div>
        @elseif($view_mode !== 'report_card' && $this->results->isEmpty())
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center">
                <div class="text-gray-400 text-lg">No results found. Enter marks and calculate results first.</div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
