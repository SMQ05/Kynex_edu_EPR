<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->teacherHasNoAssignments())
            <div class="fi-section rounded-xl bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-700 p-6">
                <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100">No teaching assignments yet</h3>
                <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    You have not been assigned to any class/subject. Ask the school administrator to assign you in
                    <span class="font-medium">Academic Setup &rarr; Teaching Assignments</span>, then refresh this page.
                </p>
            </div>
        @endif

        {{-- Assessment type selector --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold mb-2">What are you grading?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Pick the assessment type. Marks for all types contribute to the weighted annual result, using the splits set in
                <a href="{{ route('filament.school-admin.resources.academic-years.index') }}" class="text-primary-600 hover:underline">Academic Setup &rarr; Academic Years</a>
                (default 80% exams, 10% homework, 10% class assignments).
            </p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($this->assessmentTypeOptions() as $value => $label)
                    @php
                        $active = $assessment_kind === $value;
                    @endphp
                    <button
                        type="button"
                        wire:click="$set('assessment_kind', '{{ $value }}')"
                        class="text-left p-4 rounded-lg ring-1 transition
                            {{ $active
                                ? 'bg-primary-50 ring-primary-500 text-primary-900 dark:bg-primary-900/40 dark:text-primary-100'
                                : 'bg-white ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:ring-gray-700 dark:hover:bg-gray-700 dark:text-gray-200' }}">
                        <div class="text-sm font-semibold">{{ explode(' (', $label)[0] }}</div>
                        @if(str_contains($label, '('))
                            <div class="text-xs opacity-75 mt-1">{{ '(' . explode('(', $label)[1] }}</div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Filters --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            @if($this->isExamFlow())
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Select Exam &amp; Subject</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Each exam carries its own weightage in the annual result. Configure per-exam weightage on the Exam edit page.
                        </p>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <a href="{{ route('filament.school-admin.resources.exams.index') }}" class="text-primary-600 hover:underline dark:text-primary-400">Manage Exams &rarr;</a>
                        <a href="{{ route('filament.school-admin.pages.grading-weights') }}" class="text-primary-600 hover:underline dark:text-primary-400">Grading Weights &rarr;</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exam</label>
                        <select wire:model.live="exam_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                            <option value="">-- Select Exam --</option>
                            @foreach($this->exams as $exam)
                                @php
                                    $typeLabel = $exam->exam_type instanceof \App\Enums\ExamType
                                        ? $exam->exam_type->label()
                                        : (\App\Enums\ExamType::tryFrom((string) $exam->exam_type)?->label());
                                @endphp
                                <option value="{{ $exam->id }}">
                                    {{ $exam->name }}@if($typeLabel) &mdash; {{ $typeLabel }} @if($exam->weightage_percent !== null) ({{ $exam->weightage_percent }}%) @endif @endif
                                </option>
                            @endforeach
                        </select>
                        @if($this->exams->isEmpty())
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                No exams available. Ask an admin to create one in Examinations &rarr; Exams.
                            </p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                        <select wire:model.live="class_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                            <option value="">-- Select Class --</option>
                            @foreach($this->classOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @if(empty($this->classOptions) && $exam_id)
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                No classes are linked to this exam. Add an Exam Schedule for this class/subject.
                            </p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section</label>
                        <select wire:model.live="section_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                            <option value="">-- All Sections --</option>
                            @foreach($this->sectionOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                        <select wire:model.live="subject_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                            <option value="">-- Select Subject --</option>
                            @foreach($this->subjectOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @else
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Select Assignment</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Pick a {{ str_replace('_', ' ', $assessment_kind) }} from the list. Only assignments with a Total Marks value appear here.
                        </p>
                    </div>
                    <a href="{{ route('filament.school-admin.resources.homework-assignments.create') }}" class="text-sm text-primary-600 hover:underline dark:text-primary-400">
                        Create new {{ str_replace('_', ' ', $assessment_kind) }} &rarr;
                    </a>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assignment</label>
                    <select wire:model.live="homework_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                        <option value="">-- Select Assignment --</option>
                        @foreach($this->homeworkOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(empty($this->homeworkOptions))
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                            No matching {{ str_replace('_', ' ', $assessment_kind) }} assignments with Total Marks set.
                            <a href="{{ route('filament.school-admin.resources.homework-assignments.create') }}" class="text-primary-600 underline">Create one &rarr;</a>
                        </p>
                    @endif
                </div>
            @endif

            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button wire:click="loadStudents" color="primary" icon="heroicon-o-magnifying-glass">
                    Load Students
                </x-filament::button>

                @if($this->isExamFlow() && $exam_id)
                    <x-filament::button wire:click="calculateResults" color="success" icon="heroicon-o-calculator">
                        Calculate Exam Results
                    </x-filament::button>
                @endif
            </div>
        </div>

        {{-- Marks Entry Table --}}
        @if(!empty($marks))
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Enter Marks</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $context_label }} &middot; Full: {{ $full_marks }} &middot; Pass: {{ $pass_marks }}
                        </p>
                    </div>
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
                                @if($this->isExamFlow())
                                    <th class="px-4 py-3 font-medium text-center">Absent</th>
                                @endif
                                <th class="px-4 py-3 font-medium">{{ $this->isExamFlow() ? 'Remarks' : 'Feedback' }}</th>
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
                                    @if($this->isExamFlow())
                                        <td class="px-4 py-2 text-center">
                                            <input
                                                type="checkbox"
                                                wire:model.live="marks.{{ $index }}.is_absent"
                                                class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                                            />
                                        </td>
                                    @endif
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
