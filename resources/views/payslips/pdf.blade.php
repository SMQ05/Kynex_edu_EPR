<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payroll->staffProfile?->schoolUser?->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #2563eb;
            font-size: 22px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
        }
        .payslip-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            background: #f3f4f6;
            padding: 8px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-grid td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .info-grid .label {
            font-weight: bold;
            color: #555;
            width: 30%;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .salary-table th {
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            text-align: left;
        }
        .salary-table td {
            padding: 6px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .salary-table .amount {
            text-align: right;
            font-family: monospace;
        }
        .salary-table .total-row {
            background: #f9fafb;
            font-weight: bold;
        }
        .salary-table .net-row {
            background: #dbeafe;
            font-weight: bold;
            font-size: 14px;
        }
        .signature-section {
            margin-top: 60px;
            width: 100%;
        }
        .signature-section td {
            width: 33%;
            text-align: center;
            padding-top: 40px;
        }
        .signature-line {
            border-top: 1px solid #999;
            display: inline-block;
            width: 150px;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'KynexEdu ERP') }}</h1>
        <p>Payslip for the Month of {{ \Carbon\Carbon::create()->month($payroll->month)->format('F') }} {{ $payroll->year }}</p>
    </div>

    <div class="payslip-title">SALARY SLIP</div>

    <table class="info-grid">
        <tr>
            <td class="label">Employee Name:</td>
            <td>{{ $payroll->staffProfile?->schoolUser?->name ?? 'N/A' }}</td>
            <td class="label">Employee ID:</td>
            <td>{{ $payroll->staffProfile?->employee_id ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Designation:</td>
            <td>{{ $payroll->staffProfile?->designation?->name ?? 'N/A' }}</td>
            <td class="label">Department:</td>
            <td>{{ $payroll->staffProfile?->department?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Month/Year:</td>
            <td>{{ \Carbon\Carbon::create()->month($payroll->month)->format('F') }} {{ $payroll->year }}</td>
            <td class="label">Payment Date:</td>
            <td>{{ $payroll->paid_at?->format('d M Y') ?? 'Pending' }}</td>
        </tr>
        <tr>
            <td class="label">Working Days:</td>
            <td>{{ $payroll->working_days }}</td>
            <td class="label">Present Days:</td>
            <td>{{ $payroll->present_days }}</td>
        </tr>
    </table>

    <table class="salary-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Basic Salary</strong></td>
                <td class="amount">{{ number_format($payroll->basic_salary_paisas / 100, 2) }}</td>
            </tr>

            <tr class="total-row">
                <td>Total Allowances</td>
                <td class="amount">+ {{ number_format($payroll->allowances_paisas / 100, 2) }}</td>
            </tr>

            <tr class="total-row">
                <td>Total Deductions</td>
                <td class="amount">- {{ number_format($payroll->deductions_paisas / 100, 2) }}</td>
            </tr>

            <tr class="net-row">
                <td>NET SALARY</td>
                <td class="amount">PKR {{ number_format($payroll->net_salary_paisas / 100, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signature-section">
        <tr>
            <td>
                <div class="signature-line">Employee Signature</div>
            </td>
            <td>
                <div class="signature-line">Accountant</div>
            </td>
            <td>
                <div class="signature-line">Principal / Admin</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        This is a computer-generated payslip. Generated on {{ now()->format('d M Y H:i') }}.
    </div>
</body>
</html>
