{{--
    Fee statement and payment for a guardian's children.

    Every figure comes from Fees::totals()/lineFor(), which read the StudentFee
    model's own balance accessors. The open child is re-resolved from the
    guardian's own list in the page class, so ?child= cannot be pointed at
    another family.
--}}
<x-filament-panels::page>
@include('filament.portal.styles', ['accent' => 'indigo'])

@php
    $cur   = $this->currency();
    $money = fn (int $p) => $cur . number_format($p / 100, 2);
    $child = $this->child;
    $t     = $this->totals;
@endphp

@if ($this->children->isEmpty())
    <x-filament::section>
        <p class="sp-empty">No children are linked to this account yet.</p>
    </x-filament::section>
@else
    {{-- ── Child switcher (only when there is more than one) ────────── --}}
    @if ($this->children->count() > 1)
        <x-filament::section>
            <x-slot name="heading">Choose a child</x-slot>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                @foreach ($this->children as $c)
                    <button
                        type="button"
                        wire:click="selectChild('{{ $c->id }}')"
                        class="sp-list__item {{ $child && $child->id === $c->id ? 'sp-list__item--on' : '' }}"
                        style="width:auto;"
                    >
                        <span class="sp-list__title">{{ trim($c->first_name . ' ' . $c->last_name) }}</span>
                        <span class="sp-list__meta">{{ $c->schoolClass?->name ?? '—' }}</span>
                    </button>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- ── Statement summary ───────────────────────────────────────── --}}
    <div class="sp-grid-stats">
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-ink">{{ $money($t['billed']) }}</div>
            <div class="sp-stat__label">Billed<span class="sp-stat__hint">including fines, less discounts</span></div>
        </x-filament::section>
        <x-filament::section class="sp-stat">
            <div class="sp-stat__value sp-good">{{ $money($t['paid']) }}</div>
            <div class="sp-stat__label">Paid<span class="sp-stat__hint">{{ $t['paidRatio'] !== null ? $t['paidRatio'] . '% of billed' : '—' }}</span></div>
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

    {{-- ── Pay ─────────────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Make a payment</x-slot>
        <x-slot name="description">
            {{ $child ? trim($child->first_name . ' ' . $child->last_name) : '' }}
            @if ($t['due'] > 0) · {{ $money($t['due']) }} outstanding @endif
        </x-slot>

        @if ($t['due'] <= 0)
            <p class="sp-empty">Nothing to pay — this account is fully settled.</p>
        @elseif (! $this->showForm)
            <div class="pf-pay">
                <div style="min-width:0;">
                    <div class="pf-pay__title">Pay by bank transfer</div>
                    <div class="sp-row__meta">
                        Transfer the amount to the school account, then record it here with your
                        bank reference. The office confirms it and issues a receipt against your statement.
                    </div>
                </div>
                <x-filament::button wire:click="openForm" icon="heroicon-m-banknotes">
                    Record a transfer
                </x-filament::button>
            </div>

            <p class="sp-row__meta" style="margin-top:.75rem;">
                Card payment is not enabled for this school yet. Ask the office to switch it on
                if you would prefer to pay by card.
            </p>
        @else
            <form wire:submit="submitPayment" class="pf-form">
                <div class="pf-field">
                    <label class="pf-label" for="pf-amount">Amount paid ({{ $cur }})</label>
                    <input id="pf-amount" type="text" inputmode="decimal" wire:model="amount" class="sp-ask__box">
                    <span class="sp-row__meta">Outstanding balance is {{ $money($t['due']) }}.</span>
                </div>

                <div class="pf-field">
                    <label class="pf-label" for="pf-ref">Bank reference</label>
                    <input id="pf-ref" type="text" wire:model="bankReference" class="sp-ask__box"
                           placeholder="e.g. transfer confirmation number">
                    <span class="sp-row__meta">Required, so the school can match your transfer.</span>
                </div>

                <div class="pf-field">
                    <label class="pf-label" for="pf-date">Date paid</label>
                    <input id="pf-date" type="date" wire:model="paidOn" class="sp-ask__box">
                </div>

                <div class="pf-field" style="grid-column:1 / -1;">
                    <label class="pf-label" for="pf-note">Note (optional)</label>
                    <textarea id="pf-note" rows="2" wire:model="note" class="sp-ask__box"
                              placeholder="Anything the office should know about this payment"></textarea>
                </div>

                <div style="grid-column:1 / -1;display:flex;gap:.5rem;">
                    <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="submitPayment">
                        <span wire:loading.remove wire:target="submitPayment">Submit for confirmation</span>
                        <span wire:loading wire:target="submitPayment">Submitting…</span>
                    </x-filament::button>
                    <x-filament::button color="gray" wire:click="closeForm">Cancel</x-filament::button>
                </div>
            </form>
        @endif
    </x-filament::section>

    {{-- ── Submitted transfers awaiting confirmation ───────────────── --}}
    @if ($this->requests->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Transfers you have recorded</x-slot>
            <div class="sp-table__scroll">
                <table class="sp-table">
                    <thead>
                        <tr><th>Recorded</th><th>Reference</th><th class="sp-num">Amount</th><th>Status</th><th>Receipt</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($this->requests as $r)
                            <tr>
                                <td>{{ $r->paid_on ? \Illuminate\Support\Carbon::parse($r->paid_on)->format('M j, Y') : '—' }}</td>
                                <td>{{ $r->bank_reference ?: '—' }}</td>
                                <td class="sp-num">{{ $money((int) $r->amount_paisas) }}</td>
                                <td>
                                    @if ($r->status === 'approved')
                                        <span class="sp-badge sp-badge--ok">Confirmed</span>
                                    @elseif ($r->status === 'rejected')
                                        <span class="sp-badge sp-badge--late">Not accepted</span>
                                    @else
                                        <span class="sp-badge sp-badge--wait">Awaiting confirmation</span>
                                    @endif
                                </td>
                                <td>{{ $r->receipt_number ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    {{-- ── Fee schedule ───────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Fee schedule</x-slot>
        <x-slot name="description">{{ $this->fees->count() }} {{ Str::plural('item', $this->fees->count()) }} on this account</x-slot>

        @if ($this->fees->isEmpty())
            <p class="sp-empty">No fees have been raised.</p>
        @else
            <div class="sp-table__scroll">
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th>Fee</th><th>Period</th><th>Due</th>
                            <th class="sp-num">Billed</th><th class="sp-num">Paid</th><th class="sp-num">Balance</th><th>Status</th>
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

    {{-- ── Receipts ───────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Receipts</x-slot>
        @if ($this->payments->isEmpty())
            <p class="sp-empty">No payments recorded yet.</p>
        @else
            <div class="sp-table__scroll">
                <table class="sp-table">
                    <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th class="sp-num">Amount</th></tr></thead>
                    <tbody>
                        @foreach ($this->payments as $pay)
                            <tr>
                                <td>{{ $pay->receipt_number ?? '—' }}</td>
                                <td>{{ $pay->payment_date ? \Illuminate\Support\Carbon::parse($pay->payment_date)->format('M j, Y') : '—' }}</td>
                                <td>{{ \App\Support\EnumLabel::text($pay->payment_method) }}</td>
                                <td class="sp-num">{{ $money((int) $pay->total_amount_paisas) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
@endif

<style>
    .pf-pay { display: flex; align-items: center; justify-content: space-between;
              gap: 1rem; flex-wrap: wrap; padding: .875rem 1rem; border-radius: .75rem;
              background: var(--portal-accent-bg); border: 1px solid var(--portal-accent-ring); }
    .dark .pf-pay { background: rgba(49,46,129,.3); }
    .pf-pay__title { font-weight: 600; color: #111827; }
    .dark .pf-pay__title { color: #f9fafb; }
    .pf-form  { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .pf-field { display: flex; flex-direction: column; gap: .25rem; min-width: 0; }
    .pf-label { font-size: .8125rem; font-weight: 600; color: #374151; }
    .dark .pf-label { color: #d1d5db; }
</style>
</x-filament-panels::page>
