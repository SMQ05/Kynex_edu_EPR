<?php

namespace App\Http\Controllers;

use App\Models\Tenant\StaffPayroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function download(string $payrollId)
    {
        $payroll = StaffPayroll::with([
            'staffProfile.schoolUser',
            'staffProfile.designation',
            'staffProfile.department',
        ])->findOrFail($payrollId);

        $pdf = Pdf::loadView('payslips.pdf', compact('payroll'));

        $staffName = str_replace(' ', '_', $payroll->staffProfile?->schoolUser?->name ?? 'Staff');
        $monthYear = \Carbon\Carbon::create()->month($payroll->month)->format('F') . '_' . $payroll->year;

        return $pdf->download("Payslip_{$staffName}_{$monthYear}.pdf");
    }
}
