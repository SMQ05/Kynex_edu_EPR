<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Select Exam & Class</x-slot>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            {{ $this->form }}
        </div>
    </x-filament::section>

    @php $results = $this->getResults(); @endphp

    @if($this->exam_id)
        <x-filament::section class="mt-6">
            <x-slot name="heading">Preview ({{ $results->count() }} students)</x-slot>

            @if($results->isEmpty())
                <p class="text-sm text-gray-400 py-6 text-center">
                    No computed results for this exam/class. Compute or publish results first.
                </p>
            @else
                <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-left">
                            <tr>
                                <th class="px-4 py-2 font-medium">Roll</th>
                                <th class="px-4 py-2 font-medium">Student</th>
                                <th class="px-4 py-2 font-medium">Marks</th>
                                <th class="px-4 py-2 font-medium">%</th>
                                <th class="px-4 py-2 font-medium">Grade</th>
                                <th class="px-4 py-2 font-medium">Guardian Phone</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($results as $result)
                                @php
                                    $guardian = $result->student->guardians
                                        ->sortByDesc(fn ($g) => $g->is_primary_contact ? 1 : 0)
                                        ->first(fn ($g) => filled($g->phone));
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 text-gray-500">{{ $result->student->roll_number ?? '—' }}</td>
                                    <td class="px-4 py-2">{{ $result->student->full_name }}</td>
                                    <td class="px-4 py-2">{{ rtrim(rtrim((string) $result->marks_obtained, '0'), '.') }}/{{ rtrim(rtrim((string) $result->total_marks, '0'), '.') }}</td>
                                    <td class="px-4 py-2">{{ rtrim(rtrim((string) $result->percentage, '0'), '.') }}%</td>
                                    <td class="px-4 py-2">{{ $result->grade ?? '—' }}</td>
                                    <td class="px-4 py-2 {{ $guardian ? '' : 'text-red-500' }}">
                                        {{ $guardian?->phone ?? 'No phone' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
