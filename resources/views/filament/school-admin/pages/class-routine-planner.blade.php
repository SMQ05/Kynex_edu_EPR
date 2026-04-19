<x-filament-panels::page>
<div class="space-y-5">

{{-- ══════════════════════════════════════════════════════════
     CARD 1 — Class / Year picker
══════════════════════════════════════════════════════════ --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-content p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Academic Year <span class="text-danger-600">*</span>
                </label>
                <select wire:model.live="academic_year_id"
                        class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm text-sm
                               focus:border-primary-500 focus:ring-primary-500
                               dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">— Select Year —</option>
                    @foreach (\App\Models\Tenant\AcademicYear::orderByDesc('name')->pluck('name','id') as $id => $name)
                        <option value="{{ $id }}" @selected($academic_year_id === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Class <span class="text-danger-600">*</span>
                </label>
                <select wire:model.live="class_id"
                        class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm text-sm
                               focus:border-primary-500 focus:ring-primary-500
                               dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">— Select Class —</option>
                    @foreach (\App\Models\Tenant\SchoolClass::orderBy('name')->pluck('name','id') as $id => $name)
                        <option value="{{ $id }}" @selected($class_id === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Section <span class="text-xs text-gray-400">(optional)</span>
                </label>
                <select wire:model.live="section_id"
                        class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm text-sm
                               focus:border-primary-500 focus:ring-primary-500
                               dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">All / No Section</option>
                    @foreach ($this->sections as $id => $name)
                        <option value="{{ $id }}" @selected($section_id === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-filament::button wire:click="loadRoutine" icon="heroicon-o-arrow-path" class="w-full">
                    Load / Start
                </x-filament::button>
            </div>

        </div>
    </div>
</div>

@if ($isLoaded)

{{-- ══════════════════════════════════════════════════════════
     CARD 2 — Period Generator
══════════════════════════════════════════════════════════ --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header px-5 py-4 border-b border-gray-200 dark:border-white/10">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">⚡ Generate Period Schedule</h3>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            Set the start time, how long each period lasts and how many periods — we build the timetable for you.
            You can edit any slot individually after.
        </p>
    </div>
    <div class="fi-section-content p-5">

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">

            {{-- Start Time --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    School starts at
                </label>
                <input type="time"
                       wire:model.live="gen_start_time"
                       class="fi-input block w-full rounded-lg border-gray-300 shadow-sm text-sm
                              focus:border-primary-500 focus:ring-primary-500
                              dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            {{-- Period Duration —quick pills + custom --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Period duration
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach ([30, 40, 45, 50, 60] as $min)
                    <button wire:click="$set('gen_duration_min', {{ $min }})"
                            @class([
                                'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold ring-1 transition',
                                'bg-primary-600 text-white ring-primary-600' => $gen_duration_min === $min,
                                'bg-white text-gray-700 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700' => $gen_duration_min !== $min,
                            ])>
                        {{ $min }} min
                    </button>
                    @endforeach
                    {{-- Custom input --}}
                    <div class="flex items-center gap-1">
                        <input type="number"
                               wire:model.live="gen_duration_min"
                               min="5" max="180"
                               placeholder="Custom"
                               class="fi-input w-20 rounded-lg border-gray-300 text-sm shadow-sm
                                      focus:border-primary-500 focus:ring-primary-500
                                      dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <span class="text-xs text-gray-400">min</span>
                    </div>
                </div>
            </div>

            {{-- Period Count —quick pills + custom --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Number of periods
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach ([4, 5, 6, 7, 8] as $n)
                    <button wire:click="$set('gen_period_count', {{ $n }})"
                            @class([
                                'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold ring-1 transition',
                                'bg-primary-600 text-white ring-primary-600' => $gen_period_count === $n,
                                'bg-white text-gray-700 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700' => $gen_period_count !== $n,
                            ])>
                        {{ $n }}
                    </button>
                    @endforeach
                    <div class="flex items-center gap-1">
                        <input type="number"
                               wire:model.live="gen_period_count"
                               min="1" max="20"
                               placeholder="?"
                               class="fi-input w-16 rounded-lg border-gray-300 text-sm shadow-sm
                                      focus:border-primary-500 focus:ring-primary-500
                                      dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <span class="text-xs text-gray-400">periods</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Preview line --}}
        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            Preview: <strong>{{ $gen_period_count }}</strong> × <strong>{{ $gen_duration_min }} min</strong>
            starting <strong>{{ $gen_start_time }}</strong>
            → ends at
            <strong>
                @php
                    $previewEnd = $gen_start_time;
                    [$ph, $pm] = array_map('intval', explode(':', $gen_start_time));
                    $total = $ph * 60 + $pm + ($gen_period_count * $gen_duration_min);
                    $previewEnd = sprintf('%02d:%02d', intdiv($total, 60) % 24, $total % 60);
                @endphp
                {{ $previewEnd }}
            </strong>
            (breaks not counted yet)
        </p>

        <div class="mt-4">
            <x-filament::button wire:click="generateSlots" icon="heroicon-o-sparkles" color="primary">
                Generate Schedule
            </x-filament::button>
            <span class="ml-3 text-xs text-gray-400">⚠ This replaces the current slot list (not the saved routine)</span>
        </div>

    </div>
</div>

@if (! empty($periodSlots))

{{-- ══════════════════════════════════════════════════════════
     CARD 3 — Slot editor (period times + add break)
══════════════════════════════════════════════════════════ --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-white/10">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Period Slots</h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Edit any duration — times below it update automatically.
                Use <strong>+ Break</strong> to insert a break between any two periods.
            </p>
        </div>
    </div>
    <div class="fi-section-content p-4">

        <div class="space-y-0">
        @foreach ($periodSlots as $i => $slot)
        @php $isBreak = $slot['type'] === 'break'; @endphp

        {{-- Slot row --}}
        <div wire:key="slot-{{ $i }}"
             @class([
                 'group flex items-center gap-3 rounded-lg px-3 py-2.5 transition',
                 'bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-800 mb-1' => $isBreak,
                 'bg-gray-50 dark:bg-gray-800/60 ring-1 ring-gray-200 dark:ring-gray-700 mb-1' => ! $isBreak,
             ])>

            {{-- Slot badge --}}
            <div @class([
                    'flex-shrink-0 w-24 text-center rounded-md px-2 py-1 text-xs font-bold',
                    'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' => $isBreak,
                    'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' => ! $isBreak,
                ])>
                @if ($isBreak)
                    ☕ Break
                @else
                    {{ $slot['label'] }}
                @endif
            </div>

            {{-- Label (editable for breaks) --}}
            @if ($isBreak)
            <input type="text"
                   wire:model.blur="periodSlots.{{ $i }}.label"
                   placeholder="Break label…"
                   class="fi-input flex-1 min-w-0 rounded-md border-amber-300 text-sm
                          focus:border-amber-500 focus:ring-amber-500
                          dark:border-amber-700 dark:bg-gray-800 dark:text-white" />
            @else
            <span class="flex-1 min-w-0 text-sm text-gray-600 dark:text-gray-400 truncate">
                {{ $slot['start_time'] }} → {{ $slot['end_time'] }}
            </span>
            @endif

            {{-- Duration input --}}
            <div class="flex items-center gap-1 flex-shrink-0">
                <input type="number"
                       wire:model.live="periodSlots.{{ $i }}.duration"
                       wire:change="recalcTimes"
                       min="1" max="300"
                       class="fi-input w-16 rounded-md border-gray-300 text-sm text-center
                              focus:border-primary-500 focus:ring-primary-500
                              dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                <span class="text-xs text-gray-400">min</span>
            </div>

            {{-- Start / End display --}}
            <div class="flex-shrink-0 text-xs font-mono text-gray-500 dark:text-gray-400 w-28 text-center">
                {{ $slot['start_time'] }} – {{ $slot['end_time'] }}
            </div>

            {{-- Remove --}}
            <button wire:click="removeSlot({{ $i }})"
                    class="flex-shrink-0 text-gray-300 hover:text-danger-500 dark:text-gray-600 dark:hover:text-danger-400 transition"
                    title="Remove">
                <x-heroicon-o-x-mark class="w-4 h-4" />
            </button>

        </div>

        {{-- ＋ Break button between periods (not after the last slot) --}}
        @if (! $isBreak && $i < count($periodSlots) - 1)
        <div class="flex justify-center -my-0.5 relative z-10">
            <button wire:click="addBreakAfter({{ $i }})"
                    class="inline-flex items-center gap-1 rounded-full bg-white dark:bg-gray-900
                           border border-gray-200 dark:border-gray-700
                           px-3 py-0.5 text-xs text-gray-400 hover:text-amber-600 hover:border-amber-300
                           dark:hover:text-amber-400 dark:hover:border-amber-700
                           transition shadow-sm">
                <x-heroicon-o-plus class="w-3 h-3" /> Break
            </button>
        </div>
        @endif

        @endforeach
        </div>

        {{-- Add break at end --}}
        <div class="mt-3 flex justify-center">
            <button wire:click="addBreakAfter({{ count($periodSlots) - 1 }})"
                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-900/20
                           border border-amber-200 dark:border-amber-700
                           px-4 py-1 text-xs font-medium text-amber-600 dark:text-amber-400
                           hover:bg-amber-100 dark:hover:bg-amber-900/40 transition">
                <x-heroicon-o-plus class="w-3.5 h-3.5" /> Add Break at End
            </button>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     CARD 4 — Timetable Grid
══════════════════════════════════════════════════════════ --}}
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-white/10">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Timetable Grid</h3>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Click any cell to assign Subject / Teacher / Room. Break rows are automatic.
            </p>
        </div>
        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span class="inline-flex items-center gap-1">
                <span class="w-3 h-3 rounded bg-primary-100 dark:bg-primary-900/60 inline-block ring-1 ring-primary-200 dark:ring-primary-800"></span>
                Assigned
            </span>
            <span class="inline-flex items-center gap-1">
                <span class="w-3 h-3 rounded bg-amber-100 dark:bg-amber-900/60 inline-block ring-1 ring-amber-200 dark:ring-amber-800"></span>
                Break
            </span>
            <span class="inline-flex items-center gap-1">
                <span class="w-3 h-3 rounded bg-gray-100 dark:bg-gray-800 inline-block ring-1 ring-gray-200 dark:ring-gray-700"></span>
                Empty
            </span>
        </div>
    </div>

    <div class="fi-section-content overflow-x-auto p-4">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr>
                    {{-- Period label col --}}
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-36">Slot</th>

                    @foreach ($this->days as $day)
                    <th class="px-2 py-2 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide min-w-[120px]"
                        x-data="{ open: false }">
                        <div>{{ $day->name }}</div>
                        {{-- Copy-from dropdown --}}
                        <div class="relative mt-1" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="inline-flex items-center gap-0.5 text-gray-400 hover:text-primary-600
                                           text-xs font-normal normal-case transition">
                                <x-heroicon-o-document-duplicate class="w-3 h-3" />
                                copy from…
                            </button>
                            <div x-show="open" x-cloak @click.outside="open = false"
                                 class="absolute left-1/2 -translate-x-1/2 z-20 mt-1 rounded-lg bg-white dark:bg-gray-800
                                        shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10 py-1 w-28">
                                @foreach ($this->days as $srcDay)
                                    @if ($srcDay->value !== $day->value)
                                    <button @click="open = false"
                                            wire:click="copyDay('{{ $srcDay->value }}', '{{ $day->value }}')"
                                            class="block w-full px-3 py-1.5 text-xs text-left
                                                   text-gray-700 hover:bg-gray-50
                                                   dark:text-gray-300 dark:hover:bg-white/5">
                                        {{ $srcDay->name }}
                                    </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($periodSlots as $i => $slot)
                @php $isBreak = $slot['type'] === 'break'; @endphp
                <tr wire:key="grid-row-{{ $i }}">

                    {{-- Slot label cell --}}
                    <td class="px-3 py-2 align-middle">
                        @if ($isBreak)
                            <div class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                                ☕ {{ $slot['label'] ?: 'Break' }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5">
                                {{ $slot['start_time'] }} – {{ $slot['end_time'] }}
                            </div>
                        @else
                            <div class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ $slot['label'] }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5">
                                {{ $slot['start_time'] }} – {{ $slot['end_time'] }}
                            </div>
                        @endif
                    </td>

                    {{-- Day cells --}}
                    @foreach ($this->days as $day)
                    @php
                        $key      = $day->value . '.' . $i;
                        $cell     = $grid[$key] ?? [];
                        $hasSub   = ! empty($cell['subject_id']);
                    @endphp
                    <td class="px-1.5 py-1.5 align-top">
                        @if ($isBreak)
                            <div class="h-10 rounded-lg bg-amber-50 dark:bg-amber-900/20
                                        border border-amber-200 dark:border-amber-800
                                        flex items-center justify-center
                                        text-xs text-amber-500 dark:text-amber-400">
                                ☕
                            </div>
                        @else
                            <button wire:click="openCell('{{ $day->value }}', {{ $i }})"
                                    @class([
                                        'w-full rounded-lg border px-2 py-1.5 text-left text-xs transition min-h-[52px] group',
                                        'bg-primary-50 border-primary-200 hover:bg-primary-100 dark:bg-primary-900/30 dark:border-primary-700 dark:hover:bg-primary-900/50' => $hasSub,
                                        'bg-gray-50 border-gray-200 hover:border-primary-300 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700' => ! $hasSub,
                                    ])>
                                @if ($hasSub)
                                    <div class="font-semibold text-primary-700 dark:text-primary-300 leading-tight truncate">
                                        {{ $this->subjectName($cell['subject_id']) }}
                                    </div>
                                    @if (! empty($cell['teacher_id']))
                                    <div class="text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                        {{ $this->teacherName($cell['teacher_id']) }}
                                    </div>
                                    @endif
                                    @if (! empty($cell['room_number']))
                                    <div class="text-gray-400 dark:text-gray-500 truncate">
                                        Rm {{ $cell['room_number'] }}
                                    </div>
                                    @endif
                                @else
                                    <span class="text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500">
                                        + assign
                                    </span>
                                @endif
                            </button>
                        @endif
                    </td>
                    @endforeach

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Save footer --}}
    <div class="fi-section-footer border-t border-gray-200 dark:border-white/10 px-5 py-4
                flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Saving will <strong>replace</strong> the entire routine for this class / section / year.
        </p>
        <x-filament::button wire:click="saveRoutine" icon="heroicon-o-check-circle" size="lg">
            Save Routine
        </x-filament::button>
    </div>
</div>

@endif {{-- !empty slots --}}
@endif {{-- isLoaded --}}

</div>

{{-- ══════════════════════════════════════════════════════════
     Cell Assignment Modal
══════════════════════════════════════════════════════════ --}}
@if ($showModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900
                shadow-2xl ring-1 ring-gray-950/5 dark:ring-white/10"
         wire:key="cell-modal">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-white/10">
            <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                @if ($modalSlotIndex !== null && isset($periodSlots[$modalSlotIndex]))
                    {{ $periodSlots[$modalSlotIndex]['label'] }} — {{ ucfirst($modalDay ?? '') }}
                @else
                    Assign Slot
                @endif
            </h2>
            <button wire:click="closeModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-5 py-4 space-y-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Subject <span class="text-danger-600">*</span>
                </label>
                <select wire:model.live="modalSubjectId"
                        class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm
                               focus:border-primary-500 focus:ring-primary-500
                               dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">— None —</option>
                    @foreach ($this->subjectOptions as $id => $name)
                        <option value="{{ $id }}" @selected($modalSubjectId === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Teacher
                </label>
                <select wire:model.live="modalTeacherId"
                        class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm
                               focus:border-primary-500 focus:ring-primary-500
                               dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">— None —</option>
                    @foreach ($this->teacherOptions as $id => $name)
                        <option value="{{ $id }}" @selected($modalTeacherId === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Room / Lab
                </label>
                <input type="text"
                       wire:model.live="modalRoomNumber"
                       placeholder="e.g. R-101"
                       class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm
                              focus:border-primary-500 focus:ring-primary-500
                              dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-5 py-4 border-t border-gray-200 dark:border-white/10">
            <x-filament::button wire:click="clearCell" color="danger" outlined size="sm" icon="heroicon-o-trash">
                Clear
            </x-filament::button>
            <div class="flex gap-2">
                <x-filament::button wire:click="closeModal" color="gray" outlined size="sm">
                    Cancel
                </x-filament::button>
                <x-filament::button wire:click="saveCell" icon="heroicon-o-check" size="sm">
                    Apply
                </x-filament::button>
            </div>
        </div>

    </div>
</div>
@endif

</x-filament-panels::page>
