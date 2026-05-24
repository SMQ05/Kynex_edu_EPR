<x-filament-panels::page>
    <div class="space-y-6">
        {{-- ── Filters ─────────────────────────────────────────── --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
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
                        <label class="text-sm font-medium text-gray-950 dark:text-white">Student (optional)</label>
                        <select wire:model.live="student_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">All Students</option>
                            @foreach ($this->studentsForClass as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
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

        {{-- ── AI summary ──────────────────────────────────────── --}}
        @if (filled($aiSummary))
            <div class="fi-section rounded-xl bg-primary-50 p-5 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/20">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-sparkles class="h-5 w-5 text-primary-600" />
                    <h3 class="text-sm font-semibold text-primary-700 dark:text-primary-400">AI Summary &amp; Suggested Interventions</h3>
                </div>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $aiSummary }}</p>
            </div>
        @endif

        {{-- ── Results ─────────────────────────────────────────── --}}
        @if ($isLoaded)
            @php $d = $reportData; @endphp

            @if (empty($d['rows']))
                <div class="fi-section rounded-xl bg-white p-6 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    No behaviour incidents found for the selected filters.
                </div>
            @else
                @if (! empty($d['heading']))
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $d['heading'] }}</h2>
                @endif

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Incidents</p><p class="text-xl font-bold">{{ $d['stats']['total'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-success-600">Positive</p><p class="text-xl font-bold text-success-600">{{ $d['stats']['positive'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-danger-600">Negative</p><p class="text-xl font-bold text-danger-600">{{ $d['stats']['negative'] }}</p></div>
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-xs text-gray-500">Students</p><p class="text-xl font-bold">{{ $d['stats']['students'] }}</p></div>
                </div>

                <div class="fi-section overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-start font-semibold">Roll</th>
                                <th class="px-4 py-2 text-start font-semibold">Student</th>
                                <th class="px-4 py-2 text-center font-semibold">Incidents</th>
                                <th class="px-4 py-2 text-center font-semibold text-success-600">Positive</th>
                                <th class="px-4 py-2 text-center font-semibold text-danger-600">Negative</th>
                                <th class="px-4 py-2 text-center font-semibold">Net Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($d['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ $row['roll'] ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $row['student'] }}</td>
                                    <td class="px-4 py-2 text-center">{{ $row['total'] }}</td>
                                    <td class="px-4 py-2 text-center text-success-600 font-semibold">{{ $row['positive'] }}</td>
                                    <td class="px-4 py-2 text-center text-danger-600 font-semibold">{{ $row['negative'] }}</td>
                                    <td @class(['px-4 py-2 text-center font-bold', 'text-success-600' => $row['net_points'] > 0, 'text-danger-600' => $row['net_points'] < 0])>{{ $row['net_points'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
