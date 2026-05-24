<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Select Exam & Class</x-slot>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            {{ $this->filtersForm }}
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
