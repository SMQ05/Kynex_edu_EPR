<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $payment->receipt_number }} — {{ $schoolName }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6; color: #111827; padding: 24px; line-height: 1.45; }
        .receipt { max-width: 760px; margin: 0 auto; background: #fff; padding: 32px 36px;
            border: 1px solid #e5e7eb; box-shadow: 0 4px 18px rgba(0,0,0,0.04); }
        .toolbar { max-width: 760px; margin: 0 auto 12px; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button { background: #1e3a8a; color: #fff; border: 0; padding: 8px 16px; border-radius: 6px;
            font-size: 13px; font-weight: 500; cursor: pointer; }
        .toolbar a { background: #6b7280; color: #fff; padding: 8px 16px; border-radius: 6px;
            font-size: 13px; font-weight: 500; text-decoration: none; }
        h1 { font-size: 22px; color: #1e3a8a; }
        h2 { font-size: 16px; color: #1e3a8a; margin-top: 14px; }
        .head { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 18px; }
        .head .meta { font-size: 12px; color: #4b5563; margin-top: 2px; }
        .pill { display: inline-block; background: #fef3c7; color: #92400e;
            padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 1px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin-top: 12px; }
        .grid .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #6b7280; margin-bottom: 2px; }
        .grid .val { font-size: 13px; font-weight: 500; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 13px; }
        th { background: #f3f4f6; padding: 8px 10px; text-align: left;
            font-weight: 600; border-bottom: 2px solid #d1d5db; font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.5px; color: #4b5563; }
        td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; }
        td.right, th.right { text-align: right; }
        tfoot td { font-weight: 600; background: #f9fafb; border-top: 2px solid #111; }
        .words { background: #fffbeb; border: 1px dashed #f59e0b; padding: 10px 12px;
            margin-top: 14px; font-style: italic; color: #92400e; font-size: 13px; }
        .signlines { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; }
        .signlines div { border-top: 1px solid #111; padding-top: 6px; text-align: center;
            font-size: 11px; color: #4b5563; }
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px dashed #d1d5db;
            text-align: center; font-size: 10px; color: #6b7280; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt { border: 0; box-shadow: none; padding: 16mm; }
            .toolbar { display: none !important; }
            @page { size: A5 portrait; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()" type="button">🖨 Print</button>
        <a href="javascript:history.back()">Back</a>
    </div>

    <div class="receipt">
        <div class="head">
            <h1>{{ $schoolName }}</h1>
            @if (! empty($schoolMeta['tagline']))
                <div class="meta">{{ $schoolMeta['tagline'] }}</div>
            @endif
            <div class="meta">
                @if (! empty($schoolMeta['address'])) {{ $schoolMeta['address'] }} @endif
                @if (! empty($schoolMeta['phone'])) · ☎ {{ $schoolMeta['phone'] }} @endif
                @if (! empty($schoolMeta['email'])) · ✉ {{ $schoolMeta['email'] }} @endif
            </div>
            <div style="margin-top: 8px;">
                <span class="pill">Fee Payment Receipt</span>
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="label">Receipt Number</div>
                <div class="val">{{ $payment->receipt_number }}</div>
            </div>
            <div>
                <div class="label">Date</div>
                <div class="val">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</div>
            </div>
            <div>
                <div class="label">Student Name</div>
                <div class="val">{{ $student?->full_name ?? '—' }}</div>
            </div>
            <div>
                <div class="label">Admission / Reg #</div>
                <div class="val">{{ $student?->admission_number ?? '—' }} / {{ $student?->registration_number ?? '—' }}</div>
            </div>
            <div>
                <div class="label">Class · Section</div>
                <div class="val">{{ $student?->schoolClass?->name ?? '—' }} · {{ $student?->section?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="label">Academic Year</div>
                <div class="val">{{ $student?->academicYear?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="label">Payment Method</div>
                <div class="val">{{ ucwords(str_replace('_', ' ', (string) ($payment->payment_method instanceof \BackedEnum ? $payment->payment_method->value : $payment->payment_method))) }}</div>
            </div>
            <div>
                <div class="label">Reference</div>
                <div class="val">{{ $payment->bank_reference ?: '—' }}</div>
            </div>
        </div>

        <h2>Fees Paid</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fee Type</th>
                    <th>Period</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->studentFee?->feeType?->name ?? '—' }}</td>
                        <td>
                            @if ($item->studentFee?->month)
                                {{ \Carbon\Carbon::parse($item->studentFee->month . '-01')->format('M Y') }}
                            @elseif ($item->studentFee?->academicYear?->name)
                                {{ $item->studentFee->academicYear->name }}
                            @else — @endif
                        </td>
                        <td class="right">PKR {{ number_format($item->amount_paisas / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTAL</td>
                    <td class="right">PKR {{ $totalPkr }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="words">
            <strong>Amount in words:</strong> {{ $totalInWords }}
        </div>

        @if ($payment->notes)
            <div style="margin-top: 12px; font-size: 12px; color: #4b5563;">
                <strong>Note:</strong> {{ $payment->notes }}
            </div>
        @endif

        <div class="signlines">
            <div>Cashier{{ $collector ? ' — ' . $collector->name : '' }}</div>
            <div>Authorised Signatory</div>
            <div>Parent / Guardian</div>
        </div>

        <div class="footer">
            This is a system-generated receipt. Please retain for records.
            For queries, contact the accounts office. · {{ $payment->created_at?->format('d M Y H:i') }}
        </div>
    </div>
</body>
</html>
