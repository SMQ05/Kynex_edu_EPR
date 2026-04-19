<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold mb-4">Select Exam & Subject</h3>
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
                        @foreach($this->schedules->pluck('schoolClass')->unique('id') as $cls)
                            @if($cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                    <select wire:model.live="section_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                        <option value="">-- All Sections --</option>
                        @foreach($this->schedules->where('class_id', $class_id)->pluck('section')->unique('id')->filter() as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                    <select wire:model.live="subject_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                        <option value="">-- Select Subject --</option>
                        @foreach($this->schedules->where('class_id', $class_id)->pluck('subject')->unique('id')->filter() as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <x-filament::button wire:click="loadStudentsForMarks" color="primary">
                    Load Students
                </x-filament::button>

                @if($exam_id)
                    <x-filament::button wire:click="calculateResults" color="success">
                        Calculate Results
                    </x-filament::button>
                @endif
            </div>
        </div>

        {{-- Marks Entry Table --}}
        @if(!empty($marks))
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        Enter Marks
                        <span class="text-sm text-gray-500 ml-2">(Full: {{ $full_marks }} | Pass: {{ $pass_marks }})</span>
                    </h3>
                    <x-filament::button wire:click="saveMarks" color="primary" icon="heroicon-o-check">
                        Save Marks
                    </x-filament::button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-medium">#</th>
                                <th class="px-4 py-3 font-medium">Roll No</th>
                                <th class="px-4 py-3 font-medium">Student Name</th>
                                <th class="px-4 py-3 font-medium">Marks (/ {{ $full_marks }})</th>
                                <th class="px-4 py-3 font-medium text-center">Absent</th>
                                <th class="px-4 py-3 font-medium">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($marks as $index => $entry)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $entry['is_absent'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                    <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2 font-mono">{{ $entry['roll_number'] }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $entry['student_name'] }}</td>
                                    <td class="px-4 py-2">
                                        <input
                                            type="number"
                                            wire:model.blur="marks.{{ $index }}.marks_obtained"
                                            min="0"
                                            max="{{ $full_marks }}"
                                            step="0.5"
                                            class="w-24 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm text-center {{ $entry['is_absent'] ? 'opacity-50' : '' }}"
                                            {{ $entry['is_absent'] ? 'disabled' : '' }}
                                            placeholder="--"
                                        />
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input
                                            type="checkbox"
                                            wire:model.live="marks.{{ $index }}.is_absent"
                                            class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                                        />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input
                                            type="text"
                                            wire:model.blur="marks.{{ $index }}.remarks"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm"
                                            placeholder="Optional"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-filament::button wire:click="saveMarks" color="primary" icon="heroicon-o-check" size="lg">
                        Save All Marks
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
