<x-filament-panels::page>
    @php $s = $this->summary(); @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 p-4 ring-1 ring-emerald-200/50 dark:from-emerald-950/40 dark:to-emerald-900/20">
            <div class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Today</div>
            <div class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ $s['today'] }}</div>
            <div class="mt-1 text-xs text-emerald-600">{{ $s['today_count'] }} payments</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500">This month</div>
            <div class="mt-1 text-xl font-bold">{{ $s['month'] }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $s['month_count'] }} payments</div>
        </div>
        <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:ring-amber-800/40">
            <div class="text-xs uppercase tracking-wide text-amber-800 dark:text-amber-300">Outstanding</div>
            <div class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-300">{{ $s['outstanding'] }}</div>
        </div>
        <div class="rounded-xl bg-rose-50 p-4 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:ring-rose-800/40">
            <div class="text-xs uppercase tracking-wide text-rose-800 dark:text-rose-300">Defaulters</div>
            <div class="mt-1 text-xl font-bold text-rose-800 dark:text-rose-300">{{ $s['defaulters'] }}</div>
            <div class="mt-1 text-xs text-rose-600">students past due</div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
