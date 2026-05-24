<x-filament-panels::page>

    {{-- Schedule selector --}}
    <div class="flex flex-wrap items-end gap-4 mb-6">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Exam Schedule</label>
            <select wire:model.live="selectedScheduleId"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm px-3 py-2 min-w-[360px]">
                <option value="">— Select an exam schedule —</option>
                @foreach($this->getScheduleOptions() as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @php
        $schedule = $this->getSelectedSchedule();
        $roster   = $this->getRoster();
        $present  = $roster->where('status', 'present')->count();
        $absent   = $roster->where('status', 'absent')->count();
    @endphp

    @if(! $schedule)
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <x-heroicon-o-clipboard-document-check class="w-12 h-12 mb-3 opacity-40" />
            <p class="text-sm">Select an exam schedule above to mark attendance.</p>
        </div>
    @else
        <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 p-4 mb-5 flex flex-wrap gap-6 items-center bg-gray-50 dark:bg-gray-900">
            <div class="text-sm">
                <span class="font-semibold">{{ $schedule->exam?->name }}</span>
                · {{ $schedule->schoolClass?->name }}{{ $schedule->section ? ' (' . $schedule->section->name . ')' : '' }}
                · {{ $schedule->subject?->name }}
                · {{ optional($schedule->exam_date)->format('d M Y') }}
            </div>
            <div class="flex gap-4 text-sm ml-auto">
                <span class="text-emerald-600 font-medium">Present: {{ $present }}</span>
                <span class="text-red-600 font-medium">Absent: {{ $absent }}</span>
                <span class="text-gray-500">Total: {{ $roster->count() }}</span>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200 dark:ring-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Roll</th>
                        <th class="px-4 py-2 font-medium">Student</th>
                        <th class="px-4 py-2 font-medium text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($roster as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-500">{{ $row->roll_number ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $row->name }}</td>
                            <td class="px-4 py-2">
                                <div class="flex justify-center gap-1">
                                    @foreach(['present' => 'P', 'absent' => 'A', 'late' => 'L', 'leave' => 'Lv'] as $value => $abbr)
                                        @php
                                            $active = $row->status === $value;
                                            $color = match($value) {
                                                'present' => $active ? 'bg-emerald-600 text-white' : 'text-emerald-600 ring-emerald-300',
                                                'absent'  => $active ? 'bg-red-600 text-white'     : 'text-red-600 ring-red-300',
                                                'late'    => $active ? 'bg-amber-500 text-white'   : 'text-amber-600 ring-amber-300',
                                                default   => $active ? 'bg-gray-600 text-white'    : 'text-gray-600 ring-gray-300',
                                            };
                                        @endphp
                                        <button type="button"
                                                wire:click="setStatus('{{ $row->id }}', '{{ $value }}')"
                                                class="px-2.5 py-1 rounded-md ring-1 text-xs font-semibold transition {{ $color }}">
                                            {{ $abbr }}
                                        </button>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400">No enrolled students for this class/section.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

</x-filament-panels::page>
