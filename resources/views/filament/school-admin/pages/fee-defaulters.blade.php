<x-filament-panels::page>
    @php $s = $this->summary(); @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Overdue invoices</div>
            <div class="mt-1 text-2xl font-semibold text-rose-600 dark:text-rose-400">{{ $s['invoices_overdue'] }}</div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Students with overdue</div>
            <div class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $s['students_overdue'] }}</div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total outstanding</div>
            <div class="mt-1 text-2xl font-semibold text-rose-600 dark:text-rose-400">{{ $s['total_due'] }}</div>
        </div>
    </div>

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
