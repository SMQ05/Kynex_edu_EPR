<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div class="flex gap-2 mb-6">
        <x-filament::button
            :color="$activeTab === 'build' ? 'primary' : 'gray'"
            wire:click="$set('activeTab', 'build')"
        >
            🔧 Build Report
        </x-filament::button>
        <x-filament::button
            :color="$activeTab === 'saved' ? 'primary' : 'gray'"
            wire:click="$set('activeTab', 'saved')"
        >
            📋 Saved Reports
        </x-filament::button>
    </div>

    @if($activeTab === 'build')
        <div class="space-y-6">
            {{-- Step 1: Select Data Source --}}
            <x-filament::section>
                <x-slot name="heading">Step 1 — Select Data Source</x-slot>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    @foreach(\App\Services\ReportBuilderService::getModelOptions() as $key => $label)
                        <button
                            wire:click="$set('base_model', '{{ $key }}')"
                            class="p-4 rounded-lg border-2 text-center transition-all
                                {{ $base_model === $key
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-600'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-primary-300' }}"
                        >
                            <div class="text-lg font-semibold">{{ $label }}</div>
                            <div class="text-xs text-gray-500">{{ $key }}</div>
                        </button>
                    @endforeach
                </div>
            </x-filament::section>

            @if($base_model)
                {{-- Step 2: Select Columns --}}
                <x-filament::section>
                    <x-slot name="heading">Step 2 — Select Columns</x-slot>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                        @foreach($this->availableColumns as $colKey => $colLabel)
                            <label class="flex items-center gap-2 p-2 rounded border cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800
                                {{ in_array($colKey, $selected_columns) ? 'border-primary-400 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                                <input
                                    type="checkbox"
                                    value="{{ $colKey }}"
                                    wire:model.live="selected_columns"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >
                                <span class="text-sm">{{ $colLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-filament::section>

                {{-- Step 3: Add Filters --}}
                <x-filament::section>
                    <x-slot name="heading">Step 3 — Filters</x-slot>
                    <div class="space-y-2">
                        @foreach($filters as $idx => $filter)
                            <div class="flex gap-2 items-end">
                                <select wire:model.live="filters.{{ $idx }}.column" class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                    <option value="">Column...</option>
                                    @foreach($this->availableColumns as $colKey => $colLabel)
                                        <option value="{{ $colKey }}">{{ $colLabel }}</option>
                                    @endforeach
                                </select>
                                <select wire:model="filters.{{ $idx }}.operator" class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                    <option value="equals">Equals</option>
                                    <option value="contains">Contains</option>
                                    <option value="greater_than">Greater Than</option>
                                    <option value="less_than">Less Than</option>
                                    <option value="between">Between</option>
                                </select>
                                <input type="text" wire:model="filters.{{ $idx }}.value" placeholder="Value"
                                    class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm flex-1">
                                <button wire:click="$set('filters', {{ json_encode(collect($filters)->forget($idx)->values()->toArray()) }})"
                                    class="text-red-500 hover:text-red-700 text-sm px-2">✕</button>
                            </div>
                        @endforeach
                        <x-filament::button size="sm" color="gray"
                            wire:click="$set('filters', {{ json_encode(array_merge($filters, [['column' => '', 'operator' => 'equals', 'value' => '']])) }})">
                            + Add Filter
                        </x-filament::button>
                    </div>
                </x-filament::section>

                {{-- Step 4: Sort & Group --}}
                <x-filament::section>
                    <x-slot name="heading">Step 4 — Sort & Group</x-slot>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sort By</label>
                            <select wire:model="sort_by" class="mt-1 w-full fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="">None</option>
                                @foreach($selected_columns as $col)
                                    <option value="{{ $col }}">{{ $this->availableColumns[$col] ?? $col }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Direction</label>
                            <select wire:model="sort_direction" class="mt-1 w-full fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="asc">Ascending ↑</option>
                                <option value="desc">Descending ↓</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Group By</label>
                            <select wire:model="group_by" class="mt-1 w-full fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="">None</option>
                                @foreach($selected_columns as $col)
                                    <option value="{{ $col }}">{{ $this->availableColumns[$col] ?? $col }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Step 5: Run & Export --}}
                <x-filament::section>
                    <x-slot name="heading">Step 5 — Run & Export</x-slot>
                    <div class="flex flex-wrap gap-3 mb-4">
                        <x-filament::button wire:click="runReport" color="success" icon="heroicon-o-play">
                            Run Report
                        </x-filament::button>
                        <x-filament::button wire:click="exportReport('xlsx')" color="info" icon="heroicon-o-arrow-down-tray">
                            Export Excel
                        </x-filament::button>
                        <x-filament::button wire:click="exportReport('pdf')" color="danger" icon="heroicon-o-document">
                            Export PDF
                        </x-filament::button>
                        <x-filament::button wire:click="exportReport('csv')" color="gray" icon="heroicon-o-document-text">
                            Export CSV
                        </x-filament::button>
                    </div>

                    {{-- Save Report --}}
                    <div class="border-t pt-4 mt-4 space-y-3">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300">Save Report</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input type="text" wire:model="report_name" placeholder="Report Name *"
                                class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <input type="text" wire:model="report_description" placeholder="Description (optional)"
                                class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model.live="is_scheduled" class="rounded border-gray-300 text-primary-600">
                                <span class="text-sm">Schedule this report</span>
                            </label>
                            @if($is_scheduled)
                                <select wire:model="schedule_frequency" class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                                <input type="email" wire:model="schedule_email" placeholder="Email for delivery"
                                    class="fi-input rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm flex-1">
                            @endif
                        </div>
                        <x-filament::button wire:click="saveReport" color="primary" icon="heroicon-o-bookmark">
                            Save Report
                        </x-filament::button>
                    </div>
                </x-filament::section>

                {{-- Preview Results Table --}}
                @if(count($previewResults) > 0)
                    <x-filament::section>
                        <x-slot name="heading">
                            Results Preview
                            <span class="ml-2 text-sm font-normal text-gray-500">
                                Showing {{ min(500, $totalCount) }} of {{ $totalCount }} total
                            </span>
                        </x-slot>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        @foreach(array_keys($previewResults[0] ?? []) as $header)
                                            <th class="px-3 py-2 text-left font-medium text-gray-700 dark:text-gray-300">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewResults as $row)
                                        <tr class="border-b dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900">
                                            @foreach($row as $value)
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $value ?? '—' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::section>
                @endif
            @endif
        </div>
    @else
        {{-- Saved Reports Tab --}}
        <div>
            {{ $this->table }}
        </div>
    @endif
</x-filament-panels::page>
