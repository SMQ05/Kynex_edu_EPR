<x-filament-panels::page>

    {{-- ── Session Selector ──────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end gap-4 mb-6">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Exam Session</label>
            <select wire:model.live="selectedSessionId"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm px-3 py-2 min-w-[300px]">
                <option value="">— Select a session —</option>
                @foreach($this->getSessionOptions() as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @php
        $session  = $this->getSelectedSession();
        $apps     = $this->getApplicants();
        $started  = $session?->exam_started_at;
        $checkedIn = $apps->filter(fn ($a) => $a->testAttempts->first()?->checked_in_at)->count();
        $total     = $apps->count();
    @endphp

    @if(! $session)
        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
            <x-heroicon-o-clipboard-document-check class="w-12 h-12 mb-3 opacity-40" />
            <p class="text-sm">Select a session above to manage attendance.</p>
        </div>
    @else

        {{-- ── Session Info Banner ─────────────────────────────────────────── --}}
        <div class="rounded-xl ring-1 p-4 mb-5 flex flex-wrap gap-4 items-start
            {{ $started ? 'bg-emerald-50 ring-emerald-200 dark:bg-emerald-950 dark:ring-emerald-800' : 'bg-blue-50 ring-blue-200 dark:bg-blue-950 dark:ring-blue-800' }}">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm {{ $started ? 'text-emerald-800 dark:text-emerald-300' : 'text-blue-800 dark:text-blue-300' }}">
                    {{ $session->admissionTest?->name ?? 'Admission Test' }}
                    &nbsp;·&nbsp;
                    {{ $session->schoolClass?->name ?? 'All classes' }}
                </p>
                <p class="text-xs mt-0.5 {{ $started ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400' }}">
                    <x-heroicon-o-clock class="w-3.5 h-3.5 inline -mt-0.5" />
                    Scheduled: {{ $session->scheduled_at->format('l, d M Y — H:i') }}
                    &nbsp;·&nbsp;
                    {{ $session->admissionTest?->duration_minutes ?? '?' }} min
                </p>
            </div>
            <div class="text-right">
                @if($started)
                    <span class="inline-flex items-center gap-1.5 bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 text-sm font-semibold px-3 py-1 rounded-full">
                        <x-heroicon-o-play class="w-4 h-4" />
                        Exam started {{ $started->format('H:i') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-sm font-semibold px-3 py-1 rounded-full">
                        <x-heroicon-o-clock class="w-4 h-4" />
                        Not started yet
                    </span>
                @endif
            </div>
        </div>

        {{-- ── Stats Row ────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $total }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Assigned</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-emerald-200 dark:ring-emerald-800 p-4 text-center">
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $checkedIn }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Checked In</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-rose-200 dark:ring-rose-800 p-4 text-center">
                <div class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $total - $checkedIn }}</div>
                <div class="text-xs text-gray-500 mt-0.5">Absent / Pending</div>
            </div>
        </div>

        {{-- ── Applicant List ───────────────────────────────────────────────── --}}
        @if($apps->isEmpty())
            <div class="text-center py-12 text-gray-400 text-sm">No applicants assigned to this session.</div>
        @else
            <div class="bg-white dark:bg-gray-900 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Applicant</th>
                            <th class="px-4 py-3 text-left">Class</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Checked In</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($apps as $app)
                            @php
                                $attempt   = $app->testAttempts->first();
                                $isPresent = $attempt?->checked_in_at !== null;
                                $examDone  = $attempt && $attempt->isSubmitted();
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">
                                    {{ $app->full_name }}
                                    @if($app->email)
                                        <div class="text-xs text-gray-400 font-normal">{{ $app->email }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $app->schoolClass?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($examDone)
                                        <span class="inline-flex items-center gap-1 text-xs bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 px-2 py-0.5 rounded-full font-medium">
                                            <x-heroicon-o-check-badge class="w-3.5 h-3.5" />
                                            Submitted
                                        </span>
                                    @elseif($attempt?->started_at)
                                        <span class="inline-flex items-center gap-1 text-xs bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300 px-2 py-0.5 rounded-full font-medium">
                                            <x-heroicon-o-pencil class="w-3.5 h-3.5" />
                                            In Progress
                                        </span>
                                    @elseif($isPresent)
                                        <span class="inline-flex items-center gap-1 text-xs bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300 px-2 py-0.5 rounded-full font-medium">
                                            <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                            Present
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 px-2 py-0.5 rounded-full font-medium">
                                            <x-heroicon-o-clock class="w-3.5 h-3.5" />
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $attempt?->checked_in_at?->format('H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if(! $examDone && ! $attempt?->started_at)
                                        @if($isPresent)
                                            @if(! $started)
                                                <button wire:click="undoCheckIn('{{ $app->id }}')"
                                                        class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 underline">
                                                    Undo
                                                </button>
                                            @endif
                                        @else
                                            <button wire:click="checkIn('{{ $app->id }}')"
                                                    class="inline-flex items-center gap-1 text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg font-medium transition-colors">
                                                <x-heroicon-o-check class="w-3.5 h-3.5" />
                                                Check In
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Auto-refresh every 15s while exam is running --}}
        @if($started)
            <div class="mt-4 text-xs text-gray-400 text-center" wire:poll.15000ms>
                Live — refreshes every 15 s &nbsp;·&nbsp; {{ $checkedIn }} checked in · {{ $total - $checkedIn }} absent
            </div>
        @endif

    @endif

</x-filament-panels::page>
