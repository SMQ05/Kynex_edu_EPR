<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Report — {{ $school['name'] }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f1f5f9; color: #0f172a; padding: 24px; line-height: 1.45; }
        .toolbar { max-width: 900px; margin: 0 auto 12px; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button, .toolbar a {
            padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500;
            text-decoration: none; cursor: pointer; border: 0;
        }
        .toolbar .btn-print { background: #1e3a8a; color: #fff; }
        .toolbar .btn-back { background: #6b7280; color: #fff; }
        .doc { max-width: 900px; margin: 0 auto; background: #fff; padding: 32px 36px; border: 1px solid #e2e8f0; }
        h1 { font-size: 22px; color: #1e3a8a; }
        h2 { font-size: 14px; color: #1e3a8a; margin: 22px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #1e3a8a; text-transform: uppercase; letter-spacing: 1px; }
        h3 { font-size: 13px; color: #1e3a8a; margin: 14px 0 6px; }
        .head { text-align: center; border-bottom: 3px double #1e3a8a; padding-bottom: 14px; }
        .head .school { font-size: 24px; font-weight: bold; color: #1e3a8a; }
        .head .meta { font-size: 11px; color: #475569; margin-top: 4px; }
        .head .title { font-size: 14px; color: #1e3a8a; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-top: 10px; }
        .head .window { font-size: 12px; color: #475569; margin-top: 4px; font-style: italic; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

        .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; }
        .val { font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 1px; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; }
        th { text-align: left; background: #f8fafc; font-size: 10px;
             text-transform: uppercase; letter-spacing: 0.5px; color: #475569;
             border-bottom: 2px solid #cbd5e1; }
        td.right, th.right { text-align: right; }
        tfoot td { font-weight: 700; background: #f8fafc; border-top: 2px solid #0f172a; }

        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; }
        .kpi { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; }
        .kpi .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; }
        .kpi .num { font-size: 18px; font-weight: 700; margin-top: 2px; }
        .kpi.success .num { color: #047857; }
        .kpi.danger  .num { color: #b91c1c; }
        .kpi.info    .num { color: #1e3a8a; }
        .kpi.warning .num { color: #b45309; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px;
                 font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-success { background: #d1fae5; color: #065f46; }

        .signlines { margin-top: 70px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; }
        .signlines div { border-top: 1px solid #0f172a; padding-top: 6px; text-align: center; font-size: 10px; color: #475569; }
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px dashed #cbd5e1;
                  text-align: center; font-size: 9px; color: #64748b; }
        .footer .seal { margin-top: 6px; opacity: 0.6; font-size: 8px; }
        .empty { text-align: center; color: #94a3b8; font-size: 11px; padding: 14px; }
        @media print {
            body { background: #fff; padding: 0; }
            .doc { border: 0; padding: 14mm; max-width: 100%; }
            .toolbar { display: none !important; }
            @page { size: A4 portrait; margin: 10mm; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()" type="button">🖨 Print / Save as PDF</button>
        <a class="btn-back" href="javascript:window.close();void window.history.back();">Close</a>
    </div>

    <div class="doc">
        {{-- ── HEADER ───────────────────────────────────────────── --}}
        <div class="head">
            <div class="school">{{ $school['name'] }}</div>
            @if (! empty($school['tagline']))
                <div class="meta" style="font-style: italic;">{{ $school['tagline'] }}</div>
            @endif
            <div class="meta">
                @if (! empty($school['address'])) {{ $school['address'] }} @endif
                @if (! empty($school['phone'])) · ☎ {{ $school['phone'] }} @endif
                @if (! empty($school['email'])) · ✉ {{ $school['email'] }} @endif
            </div>
            <div class="title">Financial Statement Report</div>
            <div class="window">For the period: <strong>{{ $window['label'] }}</strong></div>
        </div>

        {{-- ── REPORT META ─────────────────────────────────────── --}}
        <div class="grid-3" style="margin-top: 18px;">
            <div>
                <div class="label">Campus(es)</div>
                <div class="val">{{ $school['campus'] }}</div>
            </div>
            <div>
                <div class="label">Academic Year</div>
                <div class="val">{{ $school['year'] }}</div>
            </div>
            <div>
                <div class="label">Currency</div>
                <div class="val">PKR (Pakistani Rupee)</div>
            </div>
            <div>
                <div class="label">Prepared By</div>
                <div class="val">{{ $preparedBy['name'] }}</div>
            </div>
            <div>
                <div class="label">Role</div>
                <div class="val">{{ ucwords(str_replace('_', ' ', strtolower($preparedBy['role']))) ?: '—' }}</div>
            </div>
            <div>
                <div class="label">Generated On</div>
                <div class="val">{{ $preparedBy['generated'] }}</div>
            </div>
        </div>

        {{-- ── KPI HEADLINE ────────────────────────────────────── --}}
        <h2>Executive Summary</h2>
        <div class="kpi-grid">
            <div class="kpi info">
                <div class="lbl">Billed in Period</div>
                <div class="num">PKR {{ number_format($totals['billed'] / 100, 2) }}</div>
            </div>
            <div class="kpi success">
                <div class="lbl">Collected</div>
                <div class="num">PKR {{ number_format($totals['collected'] / 100, 2) }}</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">{{ $totals['payments_count'] }} payments received</div>
            </div>
            <div class="kpi danger">
                <div class="lbl">Approved Expenses</div>
                <div class="num">PKR {{ number_format($totals['expenses'] / 100, 2) }}</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">{{ $totals['expenses_count'] }} entries</div>
            </div>
        </div>
        <div class="kpi-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="kpi {{ $totals['net'] >= 0 ? 'success' : 'danger' }}">
                <div class="lbl">Net {{ $totals['net'] >= 0 ? 'Surplus' : 'Deficit' }}</div>
                <div class="num">PKR {{ number_format(abs($totals['net']) / 100, 2) }}</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">collected − approved expenses</div>
            </div>
            <div class="kpi warning">
                <div class="lbl">Outstanding (Snapshot)</div>
                <div class="num">PKR {{ number_format($totals['outstanding'] / 100, 2) }}</div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">all-time pending + partial invoices</div>
            </div>
        </div>

        {{-- ── INCOME BREAKDOWN ────────────────────────────────── --}}
        <h2>Income Breakdown</h2>

        <h3>By Fee Type</h3>
        @if ($by_fee_type->isEmpty())
            <div class="empty">No fees billed in this window.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Fee Type</th>
                        <th class="right"># Invoices</th>
                        <th class="right">Billed (PKR)</th>
                        <th class="right">Collected (PKR)</th>
                        <th class="right">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($by_fee_type as $r)
                        <tr>
                            <td>{{ $r->label }}</td>
                            <td class="right">{{ $r->cnt }}</td>
                            <td class="right">{{ number_format(((int)$r->billed)/100, 2) }}</td>
                            <td class="right">{{ number_format(((int)$r->collected)/100, 2) }}</td>
                            <td class="right">{{ $r->billed > 0 ? round(((int)$r->collected)*100/(int)$r->billed, 1) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>By Class</h3>
        @if ($by_class->isEmpty())
            <div class="empty">No class-level data.</div>
        @else
            <table>
                <thead><tr><th>Class</th><th class="right">Billed (PKR)</th><th class="right">Collected (PKR)</th><th class="right">Rate</th></tr></thead>
                <tbody>
                    @foreach ($by_class as $r)
                        <tr>
                            <td>{{ $r->label }}</td>
                            <td class="right">{{ number_format(((int)$r->billed)/100, 2) }}</td>
                            <td class="right">{{ number_format(((int)$r->collected)/100, 2) }}</td>
                            <td class="right">{{ $r->billed > 0 ? round(((int)$r->collected)*100/(int)$r->billed, 1) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>Payments Received — by Method</h3>
        @if ($by_payment_method->isEmpty())
            <div class="empty">No payments in this window.</div>
        @else
            <table>
                <thead><tr><th>Method</th><th class="right"># Receipts</th><th class="right">Total (PKR)</th></tr></thead>
                <tbody>
                    @foreach ($by_payment_method as $r)
                        <tr>
                            <td>{{ ucfirst((string) $r->label) }}</td>
                            <td class="right">{{ $r->cnt }}</td>
                            <td class="right">{{ number_format(((int)$r->total)/100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ── EXPENSE BREAKDOWN ───────────────────────────────── --}}
        <h2>Expense Breakdown (Approved Only)</h2>

        <h3>By Category</h3>
        @if ($by_expense_category->isEmpty())
            <div class="empty">No approved expenses in this window.</div>
        @else
            <table>
                <thead><tr><th>Category</th><th class="right"># Entries</th><th class="right">Amount (PKR)</th></tr></thead>
                <tbody>
                    @foreach ($by_expense_category as $r)
                        <tr>
                            <td>{{ $r->label }}</td>
                            <td class="right">{{ $r->cnt }}</td>
                            <td class="right">{{ number_format(((int)$r->total)/100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="right">{{ $totals['expenses_count'] }}</td>
                        <td class="right">{{ number_format($totals['expenses'] / 100, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <h3>Top Individual Expenses (max 20)</h3>
        @if ($top_expenses->isEmpty())
            <div class="empty">No approved individual expenses to list.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Title</th>
                        <th>Recorded by</th>
                        <th>Approved by</th>
                        <th class="right">Amount (PKR)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($top_expenses as $e)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($e->expense_date)->format('d M Y') }}</td>
                            <td>{{ $e->category?->name ?? 'Uncategorised' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) $e->title, 35) }}</td>
                            <td>{{ $e->recorder?->name ?? '—' }}</td>
                            <td>{{ $e->approver?->name ?? '—' }}</td>
                            <td class="right">{{ number_format(((int)$e->amount_paisas)/100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ── DISCLOSURES (PENDING ITEMS) ────────────────────── --}}
        @if ($pending_expenses->isNotEmpty())
            <h2>Disclosures</h2>
            <p style="font-size: 11px; color: #475569; margin-top: 4px;">
                The following expenses fall in the report window but are <strong>NOT counted in the totals above</strong>
                because they are pending approval. Listed for transparency.
            </p>
            <table style="margin-top: 8px;">
                <thead><tr><th>Date</th><th>Category</th><th>Title</th><th>Recorded by</th><th class="right">Amount (PKR)</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($pending_expenses as $e)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($e->expense_date)->format('d M Y') }}</td>
                            <td>{{ $e->category?->name ?? 'Uncategorised' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) $e->title, 35) }}</td>
                            <td>{{ $e->recorder?->name ?? '—' }}</td>
                            <td class="right">{{ number_format(((int)$e->amount_paisas)/100, 2) }}</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ── SIGNATURES ───────────────────────────────────────── --}}
        <div class="signlines">
            <div>Prepared by<br><strong>{{ $preparedBy['name'] }}</strong></div>
            <div>Verified by<br>(Accountant / Bursar)</div>
            <div>Approved by<br>(Institute Head)</div>
        </div>

        <div class="footer">
            <div>This is a system-generated report. All figures in PKR (Pakistani Rupee) unless otherwise stated.</div>
            <div>Approved-expenses-only methodology applied. Pending expenses are disclosed but not aggregated.</div>
            <div class="seal">Generated by KynexEdu · {{ $preparedBy['generated'] }}</div>
        </div>
    </div>
</body>
</html>
