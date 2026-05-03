@php
    $groups = $page->timetableGroups();
    $days = $page->days();
    $schoolName = $page->schoolName();
    $campusList = $groups->pluck('campus_name')->filter()->unique()->values();
    $printedAt = now()->format('d M Y, H:i');
@endphp

<style>
    @media print {
        /* A4 landscape with tight margins so the grid fits comfortably. */
        @page { size: A4 landscape; margin: 8mm; }

        html, body {
            background: #ffffff !important;
            color: #111827 !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 10pt;
        }

        /* Hide everything by default, then re-show only the print area. */
        body > *:not(.fi-body),
        .fi-topbar,
        .fi-sidebar,
        .fi-page-header,
        .fi-breadcrumbs,
        .fi-main-ctn > *:not(.routine-print-root) {
            display: none !important;
        }

        /* Anything tagged screen-only is excluded from print. */
        .routine-screen-only { display: none !important; }

        .routine-print-root { display: block !important; padding: 0 !important; }

        /* The school + campus header repeated per card. */
        .routine-print-card-header {
            display: block !important;
            text-align: center;
            border-bottom: 2px solid #111827 !important;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .routine-print-card-header .school { font-size: 16pt; font-weight: bold; color: #111; }
        .routine-print-card-header .campus { font-size: 10pt; color: #333; margin-top: 2pt; }
        .routine-print-card-header .meta { font-size: 8pt; color: #666; margin-top: 2pt; letter-spacing: 1pt; text-transform: uppercase; }

        /* One section card per A4 page. */
        .routine-card {
            page-break-after: always;
            page-break-inside: avoid;
            break-inside: avoid;
            background: #ffffff !important;
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .routine-card:last-child { page-break-after: auto; }

        .routine-card * { color: #111 !important; box-shadow: none !important; }

        .routine-card .section-title { font-size: 14pt; font-weight: bold; margin: 4pt 0; }
        .routine-card .section-subtitle { font-size: 10pt; color: #444; margin-bottom: 8pt; }

        .routine-card table {
            border-collapse: collapse !important;
            width: 100% !important;
            font-size: 9pt;
        }
        .routine-card th, .routine-card td {
            border: 1px solid #4b5563 !important;
            background: #ffffff !important;
            padding: 4px 6px !important;
        }
        .routine-card thead th { background: #f3f4f6 !important; font-weight: 600; }

        .routine-cell-break { background: #fef3c7 !important; }
        .routine-cell-assigned { background: #eff6ff !important; }

        .routine-card a { text-decoration: none !important; color: #111 !important; }
    }
</style>

<div class="space-y-5 routine-print-root">
    <div class="routine-screen-only rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="text-center">
            <h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                {{ $schoolName }}
            </h1>
            @if ($campusList->count() === 1)
                <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Campus: {{ $campusList->first() }}
                </div>
            @elseif ($campusList->count() > 1)
                <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Campuses: {{ $campusList->join(', ') }}
                </div>
            @endif
            <div class="mt-1 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Class Timetable · Printed {{ $printedAt }}
            </div>
        </div>
    </div>

    <div class="routine-screen-only rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Academic Year</label>
                <select
                    wire:model.live="selectedAcademicYearId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All years</option>
                    @foreach ($page->academicYearOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>
                <select
                    wire:model.live="selectedClassId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All classes</option>
                    @foreach ($page->classOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Section</label>
                <select
                    wire:model.live="selectedSectionId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">All sections</option>
                    @foreach ($page->sectionOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end justify-start gap-2 md:justify-end">
                <x-filament::button
                    color="gray"
                    icon="heroicon-o-printer"
                    onclick="window.print(); return false;"
                >
                    Print
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    color="gray"
                    icon="heroicon-o-table-cells"
                    href="{{ \App\Filament\SchoolAdmin\Pages\ClassRoutinePlanner::getUrl() }}"
                >
                    Planner
                </x-filament::button>
            </div>
        </div>
    </div>

    @forelse ($groups as $group)
        <section class="routine-card overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Print-only header: each section gets its own school + campus banner so each A4 page is self-identified. --}}
            <div class="routine-print-card-header" style="display:none;">
                <div class="school">{{ $schoolName }}</div>
                @if (! empty($group['campus_name']))
                    <div class="campus">Campus: {{ $group['campus_name'] }}</div>
                @endif
                <div class="meta">{{ $group['class_name'] }} &middot; {{ $group['section_name'] }} &middot; {{ $group['academic_year_name'] }}</div>
            </div>

            <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/10 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="section-title text-base font-semibold text-gray-950 dark:text-white">
                        {{ $group['class_name'] }} Timetable
                    </h2>
                    <p class="section-subtitle mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ $group['academic_year_name'] }} · {{ $group['section_name'] }}@if (! empty($group['campus_name'])) · {{ $group['campus_name'] }}@endif
                    </p>
                </div>

                <div class="routine-screen-only flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1">
                        <span class="inline-block h-3 w-3 rounded bg-primary-100 ring-1 ring-primary-200 dark:bg-primary-900/60 dark:ring-primary-800"></span>
                        Assigned
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="inline-block h-3 w-3 rounded bg-amber-100 ring-1 ring-amber-200 dark:bg-amber-900/60 dark:ring-amber-800"></span>
                        Break
                    </span>
                    <span>{{ $group['records_count'] }} slots</span>
                </div>
            </div>

            <div class="overflow-x-auto p-4">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="w-40 px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Slot</th>
                            @foreach ($days as $day)
                                <th class="min-w-[140px] px-2 py-3 text-center text-xs font-semibold uppercase text-gray-700 dark:text-gray-300">
                                    {{ $day->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($group['slots'] as $slot)
                            <tr>
                                <td class="px-3 py-2 align-middle">
                                    <div @class([
                                        'text-xs font-semibold',
                                        'text-amber-600 dark:text-amber-400' => $slot['is_break'],
                                        'text-gray-800 dark:text-gray-200' => ! $slot['is_break'],
                                    ])>
                                        @if ($slot['is_break'])
                                            Break
                                        @else
                                            {{ $slot['label'] }}
                                        @endif
                                    </div>
                                    <div class="mt-0.5 font-mono text-xs text-gray-400 dark:text-gray-500">
                                        {{ $slot['start_time'] }} - {{ $slot['end_time'] }}
                                    </div>
                                </td>

                                @foreach ($days as $day)
                                    @php
                                        $cell = $group['cells'][$day->value][$slot['period_number']] ?? null;
                                    @endphp

                                    <td class="px-1.5 py-1.5 align-top">
                                        @if ($slot['is_break'] || $cell?->is_break)
                                            <div class="routine-cell-break flex min-h-11 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-600 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                                                {{ $cell?->break_label ?: $slot['label'] }}
                                            </div>
                                        @elseif ($cell?->subject)
                                            <a
                                                href="{{ $page->editRoutineUrl($cell) }}"
                                                class="routine-cell-assigned block min-h-14 rounded-lg border border-primary-200 bg-primary-50 px-2 py-1.5 text-left text-xs transition hover:bg-primary-100 dark:border-primary-700 dark:bg-primary-900/30 dark:hover:bg-primary-900/50"
                                            >
                                                <div class="truncate font-semibold leading-tight text-primary-700 dark:text-primary-300">
                                                    {{ $cell->subject->name }}
                                                </div>
                                                @if ($cell->teacher)
                                                    <div class="mt-0.5 truncate text-gray-500 dark:text-gray-400">
                                                        {{ $cell->teacher->name }}
                                                    </div>
                                                @endif
                                                @if ($cell->room_number)
                                                    <div class="truncate text-gray-400 dark:text-gray-500">
                                                        Room {{ $cell->room_number }}
                                                    </div>
                                                @endif
                                            </a>
                                        @else
                                            <div class="min-h-14 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs text-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-600">
                                                Empty
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">No timetable found</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Create a routine manually or use the routine planner to generate the timetable grid.
            </p>
            <div class="mt-4 routine-screen-only">
                <x-filament::button
                    tag="a"
                    icon="heroicon-o-table-cells"
                    href="{{ \App\Filament\SchoolAdmin\Pages\ClassRoutinePlanner::getUrl() }}"
                >
                    Open Routine Planner
                </x-filament::button>
            </div>
        </div>
    @endforelse
</div>
