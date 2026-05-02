<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Year selector --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-end gap-4">
                <div class="flex-1 max-w-md">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Academic Year</label>
                    <select wire:model.live="academic_year_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm">
                        @foreach($this->academicYearOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($this->editYearUrl())
                    <a href="{{ $this->editYearUrl() }}" class="text-sm text-primary-600 hover:underline dark:text-primary-400">
                        Edit Academic Year &rarr;
                    </a>
                @endif
            </div>
        </div>

        @if(! $this->academicYear)
            <div class="fi-section rounded-xl bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-700 p-6">
                <p class="text-sm text-amber-800 dark:text-amber-200">No academic year configured. Create one in Academic Setup &rarr; Academic Years.</p>
            </div>
        @else
            {{-- Annual Result Composition --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-1">Annual Result Composition &mdash; {{ $this->academicYear->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Each student's annual result is a weighted blend of three pools. The three weights must sum to 100%.
                </p>

                @php $yearTotal = $this->yearWeightTotal(); @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="rounded-lg ring-1 ring-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:ring-blue-800 p-4">
                        <div class="text-xs uppercase tracking-wide text-blue-700 dark:text-blue-300 font-semibold">Exams Pool</div>
                        <div class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-1">{{ $this->academicYear->exam_weight_percent }}%</div>
                        <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">Quarterly + Mid-Term + Semi-Final + Final</div>
                    </div>

                    <div class="rounded-lg ring-1 ring-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:ring-emerald-800 p-4">
                        <div class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300 font-semibold">Homework Pool</div>
                        <div class="text-3xl font-bold text-emerald-900 dark:text-emerald-100 mt-1">{{ $this->academicYear->homework_weight_percent }}%</div>
                        <div class="text-xs text-emerald-700 dark:text-emerald-300 mt-1">Average of all graded homework</div>
                    </div>

                    <div class="rounded-lg ring-1 ring-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:ring-amber-800 p-4">
                        <div class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300 font-semibold">Class Assignments Pool</div>
                        <div class="text-3xl font-bold text-amber-900 dark:text-amber-100 mt-1">{{ $this->academicYear->class_assignment_weight_percent }}%</div>
                        <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">Class assignments &amp; class tests</div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm font-medium {{ $yearTotal === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        Total: {{ $yearTotal }}%
                        @if($yearTotal !== 100)
                            &mdash; weights must sum to 100% to compute valid annual results.
                        @else
                            &mdash; balanced.
                        @endif
                    </div>
                    @if($this->editYearUrl())
                        <a href="{{ $this->editYearUrl() }}" class="text-sm text-primary-600 hover:underline dark:text-primary-400">Adjust pool weights &rarr;</a>
                    @endif
                </div>
            </div>

            {{-- Per-Exam Weightage --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-lg font-semibold mb-1">Exam Pool Breakdown</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Within the exam pool, each exam contributes its own weightage. Click an exam to edit its weight.
                </p>

                @if($this->exams->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        No exams configured for this academic year.
                        <a href="{{ \App\Filament\SchoolAdmin\Resources\ExamResource::getUrl('create') }}" class="text-primary-600 underline">Create one &rarr;</a>
                    </p>
                @else
                    @php $examTotal = $this->examWeightTotal(); @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Exam</th>
                                    <th class="px-4 py-3 font-medium">Type</th>
                                    <th class="px-4 py-3 font-medium">Schedule</th>
                                    <th class="px-4 py-3 font-medium">Weight</th>
                                    <th class="px-4 py-3 font-medium">Included</th>
                                    <th class="px-4 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->exams as $exam)
                                    @php
                                        $typeLabel = $exam->exam_type instanceof \App\Enums\ExamType
                                            ? $exam->exam_type->label()
                                            : (\App\Enums\ExamType::tryFrom((string) $exam->exam_type)?->label() ?? '—');
                                        $included = (bool) ($exam->include_in_annual_result ?? true);
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $included ? '' : 'opacity-50' }}">
                                        <td class="px-4 py-3 font-medium">
                                            {{ $exam->name }}
                                            @if($exam->weightage_label)
                                                <span class="text-xs text-gray-500 ml-1">({{ $exam->weightage_label }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 text-xs rounded ring-1 ring-gray-200 dark:ring-gray-700">{{ $typeLabel }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                            @if($exam->start_date && $exam->end_date)
                                                {{ \Carbon\Carbon::parse($exam->start_date)->format('d M') }}–{{ \Carbon\Carbon::parse($exam->end_date)->format('d M Y') }}
                                            @else
                                                <span class="italic text-gray-400">No dates</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono">{{ $exam->weightage_percent ?? 0 }}%</td>
                                        <td class="px-4 py-3">
                                            @if($included)
                                                <span class="text-emerald-600 dark:text-emerald-400">&check;</span>
                                            @else
                                                <span class="text-gray-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ $this->editExamUrl($exam->id) }}" class="text-sm text-primary-600 hover:underline dark:text-primary-400">Edit &rarr;</a>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 dark:bg-gray-800 font-semibold">
                                    <td class="px-4 py-3" colspan="3">Total weight of included exams</td>
                                    <td class="px-4 py-3 font-mono {{ $examTotal === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $examTotal }}%</td>
                                    <td class="px-4 py-3" colspan="2">
                                        @if($examTotal !== 100)
                                            <span class="text-xs text-amber-600 dark:text-amber-400">should sum to 100%</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
