<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Invoice — {{ $student->full_name ?? 'Student' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; line-height: 1.45; }
        .invoice { padding: 6px; }
        .head { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .head h1 { font-size: 20px; color: #1e3a8a; }
        .head .meta { font-size: 10px; color: #4b5563; margin-top: 2px; }
        .doc-title { display: inline-block; background: #1e3a8a; color: #fff; padding: 3px 14px;
            border-radius: 4px; font-size: 12px; font-weight: bold; letter-spacing: 1px; margin-top: 6px; }
        .grid { width: 100%; margin-top: 8px; }
        .grid td { padding: 3px 0; vertical-align: top; }
        .grid .label { font-size: 10px; color: #6b7280; text-transform: uppercase; width: 22%; }
        .grid .val { font-weight: bold; width: 28%; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.items th { background: #f3f4f6; padding: 7px 9px; text-align: left; font-size: 10px;
            text-transform: uppercase; color: #4b5563; border-bottom: 2px solid #d1d5db; }
        table.items td { padding: 7px 9px; border-bottom: 1px solid #f1f1f1; }
        .right { text-align: right; }
        table.items tfoot td { font-weight: bold; background: #f9fafb; border-top: 2px solid #111; }
        .summary { width: 50%; margin-left: 50%; margin-top: 10px; border-collapse: collapse; }
        .summary td { padding: 5px 9px; border-bottom: 1px solid #eee; }
        .summary .grand td { font-size: 14px; font-weight: bold; color: #1e3a8a; border-top: 2px solid #111; border-bottom: 0; }
        .words { background: #fffbeb; border: 1px dashed #f59e0b; padding: 8px 10px; margin-top: 12px;
            font-style: italic; color: #92400e; font-size: 11px; }
        .status { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 9px;
            font-weight: bold; text-transform: uppercase; }
        .s-pending { background: #fee2e2; color: #991b1b; }
        .s-partial { background: #fef3c7; color: #92400e; }
        .s-paid { background: #d1fae5; color: #065f46; }
        .footer { margin-top: 26px; padding-top: 8px; border-top: 1px dashed #d1d5db; text-align: center;
            font-size: 9px; color: #6b7280; }
        .signlines { margin-top: 44px; width: 100%; }
        .signlines td { border-top: 1px solid #111; padding-top: 5px; text-align: center;
            font-size: 10px; color: #4b5563; width: 33%; }
        .signspace td { border: 0; height: 28px; }
    </style>
</head>
<body>
<div class="invoice">
    <div class="head">
        <h1>{{ $schoolName }}</h1>
        @if (! empty($schoolMeta['tagline']))<div class="meta">{{ $schoolMeta['tagline'] }}</div>@endif
        @if (! empty($schoolMeta['address']))<div class="meta">{{ $schoolMeta['address'] }}</div>@endif
        @if (! empty($schoolMeta['phone']) || ! empty($schoolMeta['email']))
            <div class="meta">{{ trim(($schoolMeta['phone'] ?? '') . '   ' . ($schoolMeta['email'] ?? '')) }}</div>
        @endif
        <div class="doc-title">FEE INVOICE</div>
    </div>

    <table class="grid">
        <tr>
            <td class="label">Invoice No</td><td class="val">{{ $invoiceNumber }}</td>
            <td class="label">Date</td><td class="val">{{ $issuedOn }}</td>
        </tr>
        <tr>
            <td class="label">Student</td><td class="val">{{ $student->full_name ?? '—' }}</td>
            <td class="label">Adm. No</td><td class="val">{{ $student->admission_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Class</td><td class="val">{{ $student->schoolClass->name ?? '—' }}{{ $student->section ? ' / ' . $student->section->name : '' }}</td>
            <td class="label">Roll No</td><td class="val">{{ $student->roll_number ?? '—' }}</td>
        </tr>
        @if ($academicYearName)
        <tr>
            <td class="label">Academic Year</td><td class="val">{{ $academicYearName }}</td>
            <td class="label"></td><td class="val"></td>
        </tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th>Fee Type</th>
                <th>Due Date</th>
                <th class="right">Amount</th>
                <th class="right">Fine</th>
                <th class="right">Discount</th>
                <th class="right">Paid</th>
                <th class="right">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @forelse ($fees as $fee)
                @php
                    $net = (int) $fee->amount_paisas + (int) $fee->fine_paisas - (int) $fee->discount_paisas;
                    $bal = $net - (int) $fee->paid_paisas;
                    $sv = $fee->status instanceof \BackedEnum ? $fee->status->value : (string) $fee->status;
                @endphp
                <tr>
                    <td>{{ $i++ }}</td>
                    <td>{{ $fee->feeType->name ?? '—' }}{{ $fee->month ? ' (' . $fee->month . ')' : '' }}</td>
                    <td>{{ optional($fee->due_date)->format('d M Y') ?? '—' }}</td>
                    <td class="right">{{ number_format($fee->amount_paisas / 100, 2) }}</td>
                    <td class="right">{{ number_format($fee->fine_paisas / 100, 2) }}</td>
                    <td class="right">{{ number_format($fee->discount_paisas / 100, 2) }}</td>
                    <td class="right">{{ number_format($fee->paid_paisas / 100, 2) }}</td>
                    <td class="right">{{ number_format($bal / 100, 2) }}</td>
                    <td><span class="status s-{{ $sv }}">{{ ucfirst($sv) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#6b7280;padding:18px">No fees for this selection.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Total Billed</td><td class="right">PKR {{ number_format($totals['billed'] / 100, 2) }}</td></tr>
        <tr><td>Total Fine</td><td class="right">PKR {{ number_format($totals['fine'] / 100, 2) }}</td></tr>
        <tr><td>Total Discount</td><td class="right">PKR {{ number_format($totals['discount'] / 100, 2) }}</td></tr>
        <tr><td>Total Paid</td><td class="right">PKR {{ number_format($totals['paid'] / 100, 2) }}</td></tr>
        <tr class="grand"><td>Outstanding</td><td class="right">PKR {{ number_format($totals['outstanding'] / 100, 2) }}</td></tr>
    </table>

    <div class="words">Outstanding in words: {{ $outstandingInWords }}</div>

    <table class="signlines">
        <tr class="signspace"><td></td><td></td><td></td></tr>
        <tr><td>Prepared By</td><td>Accountant</td><td>Authorised Signature</td></tr>
    </table>

    <div class="footer">
        This is a computer-generated fee invoice from {{ $schoolName }}. Generated on {{ $issuedOn }}.
    </div>
</div>
</body>
</html>
