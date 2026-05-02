<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .container {
            padding: 40px;
        }

        /* ── Header ────────────────────────────────────────────── */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563EB;
            padding-bottom: 20px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .brand-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563EB;
            margin-bottom: 4px;
        }

        .brand-tagline {
            font-size: 10px;
            color: #6b7280;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .invoice-number {
            font-size: 14px;
            color: #4b5563;
        }

        /* ── Info Grid ─────────────────────────────────────────── */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .info-label {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 12px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        /* ── Status Badge ──────────────────────────────────────── */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background: #e5e7eb; color: #4b5563; }
        .status-sent { background: #dbeafe; color: #2563eb; }
        .status-paid { background: #d1fae5; color: #059669; }
        .status-overdue { background: #fee2e2; color: #dc2626; }

        /* ── Items Table ───────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background: #f3f4f6;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table th:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .items-table td:last-child {
            text-align: right;
            font-family: monospace;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        /* ── Totals ────────────────────────────────────────────── */
        .totals-wrapper {
            display: table;
            width: 100%;
        }

        .totals-spacer {
            display: table-cell;
            width: 55%;
        }

        .totals-box {
            display: table-cell;
            width: 45%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 12px;
        }

        .totals-table td:last-child {
            text-align: right;
            font-family: monospace;
        }

        .totals-table .total-row {
            border-top: 2px solid #2563EB;
            font-weight: bold;
            font-size: 16px;
            color: #2563EB;
        }

        .totals-table .total-row td {
            padding-top: 12px;
        }

        .discount-row {
            color: #059669;
        }

        /* ── Payment Instructions ──────────────────────────────── */
        .payment-box {
            margin-top: 30px;
            padding: 16px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
        }

        .payment-title {
            font-size: 12px;
            font-weight: bold;
            color: #0369a1;
            margin-bottom: 8px;
        }

        .payment-text {
            font-size: 11px;
            color: #1e40af;
            line-height: 1.6;
        }

        /* ── Footer ────────────────────────────────────────────── */
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }

        .notes-box {
            margin-top: 20px;
            padding: 12px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
        }

        .notes-label {
            font-weight: bold;
            font-size: 10px;
            color: #92400e;
            margin-bottom: 4px;
        }

        .notes-text {
            font-size: 11px;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="container">

        {{-- ── Header ──────────────────────────────────────────── --}}
        <div class="header">
            <div class="header-left">
                <div class="brand-name">KynexEdu</div>
                <div class="brand-tagline">School Information System — SaaS Platform</div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div style="margin-top: 8px;">
                    <span class="status-badge status-{{ strtolower($invoice->status->value) }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ── Billing Info ────────────────────────────────────── --}}
        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Bill To</div>
                <div class="info-value" style="font-weight: bold; font-size: 14px;">
                    {{ $tenant->school_name ?? '—' }}
                </div>
                <div class="info-value">
                    {{ $tenant->admin_name ?? '' }}<br>
                    {{ $tenant->admin_email ?? '' }}<br>
                    @if($tenant->city){{ $tenant->city }}, @endif{{ $tenant->country ?? 'Pakistan' }}
                </div>
            </div>
            <div class="info-col" style="text-align: right;">
                <div class="info-label">Invoice Date</div>
                <div class="info-value">{{ $invoice->created_at->format('F d, Y') }}</div>

                <div class="info-label">Billing Period</div>
                <div class="info-value">
                    {{ $invoice->period_start->format('M d, Y') }} — {{ $invoice->period_end->format('M d, Y') }}
                </div>

                <div class="info-label">Plan</div>
                <div class="info-value">{{ $plan->name ?? 'N/A' }}</div>
            </div>
        </div>

        {{-- ── Line Items ──────────────────────────────────────── --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
                @if($invoice->base_amount_paisas > 0)
                <tr>
                    <td>Base Subscription Fee</td>
                    <td>{{ number_format($invoice->base_amount_paisas / 100, 2) }}</td>
                </tr>
                @endif

                @if($invoice->per_student_amount_paisas > 0)
                <tr>
                    <td>Per Student Fee ({{ number_format($invoice->active_student_count) }} students)</td>
                    <td>{{ number_format($invoice->per_student_amount_paisas / 100, 2) }}</td>
                </tr>
                @endif

                @if($invoice->sms_usage_paisas > 0)
                <tr>
                    <td>SMS Usage Charges</td>
                    <td>{{ number_format($invoice->sms_usage_paisas / 100, 2) }}</td>
                </tr>
                @endif

                @if($invoice->whatsapp_usage_paisas > 0)
                <tr>
                    <td>WhatsApp Usage Charges</td>
                    <td>{{ number_format($invoice->whatsapp_usage_paisas / 100, 2) }}</td>
                </tr>
                @endif

                @if($invoice->ai_usage_paisas > 0)
                <tr>
                    <td>AI Assistant Usage</td>
                    <td>{{ number_format($invoice->ai_usage_paisas / 100, 2) }}</td>
                </tr>
                @endif

                @if($invoice->storage_overage_paisas > 0)
                <tr>
                    <td>Storage Overage</td>
                    <td>{{ number_format($invoice->storage_overage_paisas / 100, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        {{-- ── Totals ──────────────────────────────────────────── --}}
        <div class="totals-wrapper">
            <div class="totals-spacer"></div>
            <div class="totals-box">
                <table class="totals-table">
                    @php
                        $subtotal = $invoice->base_amount_paisas
                            + $invoice->per_student_amount_paisas
                            + $invoice->sms_usage_paisas
                            + $invoice->whatsapp_usage_paisas
                            + $invoice->ai_usage_paisas
                            + $invoice->storage_overage_paisas;
                    @endphp
                    <tr>
                        <td>Subtotal</td>
                        <td>PKR {{ number_format($subtotal / 100, 2) }}</td>
                    </tr>
                    @if($invoice->discount_paisas > 0)
                    <tr class="discount-row">
                        <td>Discount</td>
                        <td>- PKR {{ number_format($invoice->discount_paisas / 100, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Due</td>
                        <td>PKR {{ number_format($invoice->total_paisas / 100, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ── Notes ───────────────────────────────────────────── --}}
        @if($invoice->notes)
        <div class="notes-box">
            <div class="notes-label">Notes</div>
            <div class="notes-text">{{ $invoice->notes }}</div>
        </div>
        @endif

        {{-- ── Payment Instructions ────────────────────────────── --}}
        <div class="payment-box">
            <div class="payment-title">💳 Payment Instructions</div>
            <div class="payment-text">
                Bank: <strong>Meezan Bank Limited</strong><br>
                Account Title: <strong>Kynex Solutions</strong><br>
                IBAN: <strong>PK00MEZN0000000000000000</strong><br>
                <br>
                Please include your invoice number <strong>{{ $invoice->invoice_number }}</strong> as payment reference.<br>
                For queries, contact: <strong>billing@kynexedu.com</strong> | WhatsApp: <strong>+92 300 0000000</strong>
            </div>
        </div>

        {{-- ── Footer ──────────────────────────────────────────── --}}
        <div class="footer">
            KynexEdu — School Information System | kynexedu.com<br>
            This is a computer-generated invoice. No signature required.
        </div>

    </div>
</body>
</html>
