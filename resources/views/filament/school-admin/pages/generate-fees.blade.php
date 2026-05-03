<x-filament-panels::page>
    @php $s = $this->summary(); @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Outstanding</div>
            <div class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $s['outstanding_pkr'] }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $s['unpaid_count'] }} unpaid / partial invoices</div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid</div>
            <div class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $s['paid_count'] }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $s['waived_count'] }} waived</div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Fee Structure</div>
            <div class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ $s['fee_masters'] }}</div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">active rows in {{ $s['academic_year'] }}</div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">How fee generation works</h2>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-600 dark:text-gray-300">
            <li>Define <strong>Fee Groups</strong> (Tuition, Transport, Boarding…) and <strong>Fee Types</strong> inside them. Mark a fee type as <em>recurring</em> for monthly bills, leave it un-checked for one-off charges.</li>
            <li>Open <strong>Fee Structure</strong> and add a row per Class × Fee Type for the current academic year. Set the amount in PKR and the due day.</li>
            <li>Use <strong>Roll out monthly fees</strong> above to bulk-generate the month's invoices for any class. Re-running for the same month is safe — duplicates are skipped.</li>
            <li>Use <strong>One-time fee</strong> for ad-hoc charges (re-test fee, library fine, etc.) for an individual student.</li>
            <li>Run <strong>Apply late fees</strong> at the end of the grace window to fine overdue invoices.</li>
            <li>Collect via <strong>Fee Collection</strong>; review trends in <strong>Fee Reports</strong>.</li>
        </ol>
    </div>
</x-filament-panels::page>
