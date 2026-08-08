{{--
    Tuition statement. Amounts come from MyFees::lineFor()/totals(), which read
    the StudentFee model's own net_payable_paisas / balance_paisas accessors so
    the definition of "balance" lives in one place.
--}}
<x-filament-panels::page>
@include('filament.portal.styles')

    @php
        $t = $this->totals;
        $cur = $this->currency();
        $money = fn (int $paisas) => $cur . number_format($paisas / 100, 2);
    @endphp

    {{-- ── Statement summary ───────────────────────────────────────── --}}
    <div class="sp-grid-stats">
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-ink">{{ $money($t['billed']) }}</div>
            <div class="sp-stat__label">Billed<span class="sp-stat__hint">including fines, less discounts</span></div>
        </x-filament::section>
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-good">{{ $money($t['paid']) }}</div>
            <div class="sp-stat__label">Paid<span class="sp-stat__hint">{{ $t['paid_ratio'] !== null ? $t['paid_ratio'] . '% of billed' : '—' }}</span></div>
        </x-filament::section>
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value {{ $t['due'] > 0 ? 'sp-warn' : 'sp-good' }}">{{ $money($t['due']) }}</div>
            <div class="sp-stat__label">Outstanding<span class="sp-stat__hint">{{ $t['due'] > 0 ? 'balance remaining' : 'nothing owed' }}</span></div>
        </x-filament::section>
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value {{ $t['overdue'] > 0 ? 'sp-bad' : 'sp-good' }}">{{ $money($t['overdue']) }}</div>
            <div class="sp-stat__label">Overdue<span class="sp-stat__hint">{{ $t['overdue'] > 0 ? 'past the due date' : 'nothing overdue' }}</span></div>
        </x-filament::section>
    </div>

    @if ($t['paid_ratio'] !== null)
        <x-filament::section>
            <x-slot name="heading">Paid to date</x-slot>
            <div class="sp-bar" title="{{ $t['paid_ratio'] }}% paid">
                <div class="sp-bar__fill" style="width: {{ min(100, $t['paid_ratio']) }}%; background: #16a34a;"></div>
            </div>
            <p class="sp-row__meta" style="margin-top:.5rem;">
                {{ $money($t['paid']) }} of {{ $money($t['billed']) }} settled.
            </p>
        </x-filament::section>
    @endif

    {{-- ── Fee lines ───────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Fee schedule</x-slot>
        <x-slot name="description">{{ $this->fees->count() }} {{ Str::plural('line', $this->fees->count()) }} on your account</x-slot>

        @if ($this->fees->isEmpty())
            <p class="sp-empty">No fees have been raised on your account.</p>
        @else
            <div class="sp-table__scroll">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th>Fee</th>
                            <th>Period</th>
                            <th>Due</th>
                            <th class="sp-num">Billed</th>
                            <th class="sp-num">Paid</th>
                            <th class="sp-num">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->fees as $fee)
                            @php $l = $this->lineFor($fee); @endphp
                            <tr>
                                <td>{{ $fee->feeType?->name ?? 'Fee' }}</td>
                                <td>{{ $fee->month ?: '—' }}</td>
                                <td>{{ $fee->due_date ? $fee->due_date->format('M j, Y') : '—' }}</td>
                                <td class="sp-num">{{ $money($l['billed']) }}</td>
                                <td class="sp-num">{{ $money($l['paid']) }}</td>
                                <td class="sp-num">{{ $money($l['due']) }}</td>
                                <td>
                                    @if ($l['due'] <= 0)
                                        <span class="sp-badge sp-badge--ok">Paid</span>
                                    @elseif ($l['overdue'])
                                        <span class="sp-badge sp-badge--late">Overdue</span>
                                    @elseif ($l['paid'] > 0)
                                        <span class="sp-badge sp-badge--wait">Part paid</span>
                                    @else
                                        <span class="sp-badge sp-badge--due">Due</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- ── Receipts ────────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Payment history</x-slot>
        <x-slot name="description">Receipts issued against your account</x-slot>

        @if ($this->payments->isEmpty())
            <p class="sp-empty">No payments recorded yet.</p>
        @else
            <div class="sp-table__scroll">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="sp-num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->payments as $pay)
                            <tr>
                                <td>{{ $pay->receipt_number ?? '—' }}</td>
                                <td>{{ $pay->payment_date ? \Illuminate\Support\Carbon::parse($pay->payment_date)->format('M j, Y') : '—' }}</td>
                                <td>{{ \App\Support\EnumLabel::text($pay->payment_method) }}</td>
                                <td>{{ $pay->bank_reference ?: '—' }}</td>
                                <td class="sp-num">{{ $money((int) $pay->total_amount_paisas) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
