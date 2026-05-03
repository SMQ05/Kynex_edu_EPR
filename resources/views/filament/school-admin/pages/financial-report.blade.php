<x-filament-panels::page>
    <style>
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            html, body { background: #fff !important; color: #000 !important; }
            body > *:not(.fi-body), .fi-topbar, .fi-sidebar, .fi-page-header,
            .fi-breadcrumbs, .fr-toolbar { display: none !important; }
            .fi-section, .rounded-xl { box-shadow: none !important; border: 1px solid #d1d5db !important; }
            .fr-print-header { display: block !important; text-align: center; border-bottom: 2px solid #000;
                padding-bottom: 8px; margin-bottom: 12px; }
            .fr-print-header .school { font-size: 18pt; font-weight: bold; }
            .fr-print-header .meta { font-size: 9pt; color: #444; margin-top: 2pt; }
            * { color: #000 !important; }
            table { font-size: 9pt; }
            th, td { padding: 4px 6px !important; border: 1px solid #aaa !important; }
        }
        .fr-print-header { display: none; }
    </style>

    {{-- Print header (only visible in print) --}}
    <div class="fr-print-header">
        <div class="school">{{ optional(tenant())->school_name ?? config('app.name') }}</div>
        <div class="meta">Financial Report · {{ $this->summaryData()['window_label'] }} · Generated {{ now()->format('d M Y, H:i') }}</div>
    </div>

    {{-- Toolbar: presets + custom range + mode + print --}}
    <div class="fr-toolbar rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs uppercase tracking-wide text-gray-500">Preset:</span>
            @foreach ([
                'month' => 'This month',
                'last_month' => 'Last month',
                'last_30' => 'Last 30 days',
                'last_90' => 'Last 90 days',
                'year' => 'This year',
                'last_year' => 'Last year',
                'ay' => 'Academic year',
            ] as $key => $label)
                <button
                    type="button"
                    wire:click="applyPreset('{{ $key }}')"
                    @class([
                        'px-3 py-1 text-xs rounded-md font-medium transition',
                        'bg-primary-600 text-white' => $this->preset === $key,
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' => $this->preset !== $key,
                    ])
                >{{ $label }}</button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label class="text-xs uppercase tracking-wide text-gray-500">Custom range:</label>
            <input type="date" wire:model="from"
                class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"/>
            <span class="text-gray-400">→</span>
            <input type="date" wire:model="to"
                class="rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"/>
            <button type="button" wire:click="applyCustomRange"
                class="px-3 py-1 text-xs rounded-md font-medium bg-emerald-600 text-white hover:bg-emerald-700">Apply</button>

            <div class="ml-auto flex items-center gap-2">
                <button type="button" wire:click="setMode('summary')"
                    @class(['px-3 py-1 text-xs rounded-md font-medium',
                        'bg-primary-600 text-white' => $this->mode === 'summary',
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => $this->mode !== 'summary'])
                >Summary</button>
                <button type="button" wire:click="setMode('detailed')"
                    @class(['px-3 py-1 text-xs rounded-md font-medium',
                        'bg-primary-600 text-white' => $this->mode === 'detailed',
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => $this->mode !== 'detailed'])
                >Detailed</button>
                <a target="_blank"
                   href="{{ route('financial-report.print', ['from' => $from, 'to' => $to]) }}"
                   class="px-3 py-1 text-xs rounded-md font-medium bg-blue-600 text-white hover:bg-blue-700 inline-flex items-center gap-1"
                   title="Open the printable, signed report in a new tab">
                    🖨 Print Report
                </a>
            </div>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400">
            Window: <strong>{{ $this->summaryData()['window_label'] }}</strong>
        </div>
    </div>

    @if ($this->mode === 'summary')
        @php $s = $this->summaryData(); @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 p-5 dark:from-emerald-950/40 dark:to-emerald-900/20 ring-1 ring-emerald-200/50">
                <div class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Collected (in window)</div>
                <div class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $s['collected'] }}</div>
                <div class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ $s['payments_count'] }} payments · avg {{ $s['avg_payment_pkr'] }}</div>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-rose-50 to-rose-100 p-5 dark:from-rose-950/40 dark:to-rose-900/20 ring-1 ring-rose-200/50">
                <div class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-300">Expenses (in window)</div>
                <div class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-300">{{ $s['expenses'] }}</div>
                <div class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $s['expenses_count'] }} expense entries</div>
            </div>
            <div @class([
                'rounded-xl p-5 ring-1',
                'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/40 dark:to-blue-900/20 ring-blue-200/50' => ! $s['net_negative'],
                'bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/40 dark:to-amber-900/20 ring-amber-200/50' => $s['net_negative'],
            ])>
                <div class="text-xs uppercase tracking-wide {{ $s['net_negative'] ? 'text-amber-700 dark:text-amber-300' : 'text-blue-700 dark:text-blue-300' }}">
                    Net {{ $s['net_negative'] ? 'Deficit' : 'Surplus' }}
                </div>
                <div class="mt-1 text-2xl font-bold {{ $s['net_negative'] ? 'text-amber-700 dark:text-amber-300' : 'text-blue-700 dark:text-blue-300' }}">{{ $s['net'] }}</div>
                <div class="mt-1 text-xs">collected − expenses</div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs uppercase tracking-wide text-gray-500">Total Billed</div>
                <div class="mt-1 text-lg font-semibold">{{ $s['billed'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs uppercase tracking-wide text-gray-500">Collection Rate</div>
                <div class="mt-1 text-lg font-semibold">{{ $s['collection_rate'] }}%</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs uppercase tracking-wide text-gray-500">Discounts</div>
                <div class="mt-1 text-lg font-semibold">{{ $s['discounts'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-xs uppercase tracking-wide text-gray-500">Late Fees</div>
                <div class="mt-1 text-lg font-semibold">{{ $s['fines'] }}</div>
            </div>
        </div>

        <div class="rounded-xl bg-amber-50 p-5 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:ring-amber-800/40">
            <div class="text-xs uppercase tracking-wide text-amber-800 dark:text-amber-300">Outstanding (snapshot, all-time pending + partial)</div>
            <div class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-300">{{ $s['outstanding'] }}</div>
        </div>

    @else
        @php $d = $this->detailedData(); @endphp

        {{-- Daily cash-flow --}}
        @if (count($d['days']) <= 100)
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Daily cash flow · {{ $d['window_label'] }}</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500">
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-right">Collected</th>
                                <th class="px-3 py-2 text-right">Expenses</th>
                                <th class="px-3 py-2 text-right">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($d['days'] as $row)
                                @if ($row['collected'] || $row['expenses'])
                                    <tr>
                                        <td class="px-3 py-1.5">{{ $row['date'] }}</td>
                                        <td class="px-3 py-1.5 text-right font-mono text-emerald-700 dark:text-emerald-400">{{ $row['collected'] ? 'PKR ' . number_format($row['collected']/100, 2) : '—' }}</td>
                                        <td class="px-3 py-1.5 text-right font-mono text-rose-700 dark:text-rose-400">{{ $row['expenses'] ? 'PKR ' . number_format($row['expenses']/100, 2) : '—' }}</td>
                                        <td @class([
                                            'px-3 py-1.5 text-right font-mono',
                                            'text-emerald-700' => $row['net'] >= 0,
                                            'text-rose-700' => $row['net'] < 0,
                                        ])>PKR {{ number_format($row['net']/100, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                            @if (collect($d['days'])->sum('collected') === 0 && collect($d['days'])->sum('expenses') === 0)
                                <tr><td colspan="4" class="py-4 text-center text-gray-400">No transactions in this window.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Month-by-month roll-up --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Month-by-month P&amp;L</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500">
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="px-3 py-2 text-left">Month</th>
                            <th class="px-3 py-2 text-right">Collected</th>
                            <th class="px-3 py-2 text-right">Expenses</th>
                            <th class="px-3 py-2 text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($d['months'] as $row)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $row['label'] }}</td>
                                <td class="px-3 py-2 text-right font-mono text-emerald-700">PKR {{ number_format($row['collected']/100, 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-rose-700">PKR {{ number_format($row['expenses']/100, 2) }}</td>
                                <td @class([
                                    'px-3 py-2 text-right font-mono font-semibold',
                                    'text-emerald-700' => $row['net'] >= 0,
                                    'text-rose-700' => $row['net'] < 0,
                                ])>PKR {{ number_format($row['net']/100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-300">
                        <tr class="font-semibold">
                            <td class="px-3 py-2">Total</td>
                            <td class="px-3 py-2 text-right font-mono">PKR {{ number_format($d['total_collected']/100, 2) }}</td>
                            <td class="px-3 py-2 text-right font-mono">PKR {{ number_format($d['total_expenses']/100, 2) }}</td>
                            <td @class([
                                'px-3 py-2 text-right font-mono',
                                'text-emerald-700' => $d['net'] >= 0,
                                'text-rose-700' => $d['net'] < 0,
                            ])>PKR {{ number_format($d['net']/100, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Three breakdown columns --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-sm font-semibold">Income by fee type</h3>
                <table class="mt-3 w-full text-xs">
                    <thead class="text-gray-500">
                        <tr class="border-b">
                            <th class="py-1 text-left">Type</th>
                            <th class="py-1 text-right">Billed</th>
                            <th class="py-1 text-right">Collected</th>
                            <th class="py-1 text-right">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($d['by_fee_type'] as $r)
                            <tr>
                                <td class="py-1">{{ $r['label'] }}</td>
                                <td class="py-1 text-right font-mono">{{ number_format($r['billed']/100, 0) }}</td>
                                <td class="py-1 text-right font-mono">{{ number_format($r['collected']/100, 0) }}</td>
                                <td class="py-1 text-right">{{ $r['rate'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-gray-400">No income</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-sm font-semibold">Income by class</h3>
                <table class="mt-3 w-full text-xs">
                    <thead class="text-gray-500">
                        <tr class="border-b">
                            <th class="py-1 text-left">Class</th>
                            <th class="py-1 text-right">Billed</th>
                            <th class="py-1 text-right">Collected</th>
                            <th class="py-1 text-right">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($d['by_class'] as $r)
                            <tr>
                                <td class="py-1">{{ $r['label'] }}</td>
                                <td class="py-1 text-right font-mono">{{ number_format($r['billed']/100, 0) }}</td>
                                <td class="py-1 text-right font-mono">{{ number_format($r['collected']/100, 0) }}</td>
                                <td class="py-1 text-right">{{ $r['rate'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-gray-400">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-sm font-semibold">Income by payment method</h3>
                <table class="mt-3 w-full text-xs">
                    <thead class="text-gray-500">
                        <tr class="border-b">
                            <th class="py-1 text-left">Method</th>
                            <th class="py-1 text-right">Total</th>
                            <th class="py-1 text-right">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($d['by_payment_method'] as $r)
                            <tr>
                                <td class="py-1">{{ $r['label'] }}</td>
                                <td class="py-1 text-right font-mono">{{ number_format($r['total']/100, 0) }}</td>
                                <td class="py-1 text-right">{{ $r['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-3 text-center text-gray-400">No payments</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Expenses --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-sm font-semibold">Expenses by category</h3>
                <table class="mt-3 w-full text-xs">
                    <thead class="text-gray-500">
                        <tr class="border-b">
                            <th class="py-1 text-left">Category</th>
                            <th class="py-1 text-right">Total</th>
                            <th class="py-1 text-right">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($d['by_expense_category'] as $r)
                            <tr>
                                <td class="py-1">{{ $r['label'] }}</td>
                                <td class="py-1 text-right font-mono">{{ number_format($r['total']/100, 0) }}</td>
                                <td class="py-1 text-right">{{ $r['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-3 text-center text-gray-400">No expenses recorded in window</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-sm font-semibold">Top 10 expenses</h3>
                <table class="mt-3 w-full text-xs">
                    <thead class="text-gray-500">
                        <tr class="border-b">
                            <th class="py-1 text-left">Date</th>
                            <th class="py-1 text-left">Category</th>
                            <th class="py-1 text-left">Description</th>
                            <th class="py-1 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($d['top_expenses'] as $r)
                            <tr>
                                <td class="py-1 whitespace-nowrap">{{ $r['date'] }}</td>
                                <td class="py-1">{{ $r['category'] }}</td>
                                <td class="py-1 truncate max-w-xs">{{ \Illuminate\Support\Str::limit($r['description'], 50) }}</td>
                                <td class="py-1 text-right font-mono">{{ number_format($r['amount']/100, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-3 text-center text-gray-400">No expenses</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Defaulter aging snapshot --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-sm font-semibold">Defaulter aging (current snapshot · all overdue invoices)</h3>
            <p class="mt-1 text-xs text-gray-500">
                Total overdue: PKR {{ number_format($d['aging']['total_due_paisas']/100, 2) }}
            </p>
            <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-md bg-amber-50 p-3 ring-1 ring-amber-200">
                    <div class="text-xs uppercase tracking-wide text-amber-800">1–30 days late</div>
                    <div class="mt-1 text-xl font-bold text-amber-800">{{ $d['aging']['b1'] }}</div>
                </div>
                <div class="rounded-md bg-orange-50 p-3 ring-1 ring-orange-200">
                    <div class="text-xs uppercase tracking-wide text-orange-800">31–60 days</div>
                    <div class="mt-1 text-xl font-bold text-orange-800">{{ $d['aging']['b2'] }}</div>
                </div>
                <div class="rounded-md bg-red-50 p-3 ring-1 ring-red-200">
                    <div class="text-xs uppercase tracking-wide text-red-800">61–90 days</div>
                    <div class="mt-1 text-xl font-bold text-red-800">{{ $d['aging']['b3'] }}</div>
                </div>
                <div class="rounded-md bg-rose-100 p-3 ring-1 ring-rose-300">
                    <div class="text-xs uppercase tracking-wide text-rose-900">90+ days</div>
                    <div class="mt-1 text-xl font-bold text-rose-900">{{ $d['aging']['b4'] }}</div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
