<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant\FeePayment;
use Illuminate\Contracts\View\View;

/**
 * Renders a printable HTML receipt for a fee payment. Tenancy must
 * already be initialised by the route group (works on tenant
 * subdomains and on the central domain via session tenant_id).
 */
class FeeReceiptController extends Controller
{
    public function show(string $payment): View
    {
        $row = FeePayment::query()
            ->with([
                'student.schoolClass',
                'student.section',
                'student.academicYear',
                'student.guardians',
                'collector',
                'items.studentFee.feeType',
                'items.studentFee.academicYear',
            ])
            ->where('id', $payment)
            ->firstOrFail();

        $tenant = function_exists('tenant') ? tenant() : null;

        $totalPaisas = (int) $row->total_amount_paisas;

        return view('fees.receipt', [
            'payment'      => $row,
            'student'      => $row->student,
            'items'        => $row->items,
            'collector'    => $row->collector,
            'schoolName'   => $tenant?->school_name ?? config('app.name', 'School'),
            'schoolMeta'   => [
                'tagline' => optional($tenant)->tagline,
                'address' => optional($tenant)->address,
                'phone'   => optional($tenant)->phone,
                'email'   => optional($tenant)->email,
            ],
            'totalPkr'     => number_format($totalPaisas / 100, 2),
            'totalInWords' => self::numberToWords((int) round($totalPaisas / 100)),
        ]);
    }

    /** Converts an integer rupee amount to English words (Indian numbering). */
    public static function numberToWords(int $n): string
    {
        if ($n === 0) return 'Zero';
        $units = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        $under100 = function (int $x) use ($units, $tens) {
            if ($x < 20) return $units[$x];
            return trim($tens[intdiv($x, 10)] . ' ' . ($x % 10 ? $units[$x % 10] : ''));
        };

        $under1000 = function (int $x) use ($under100, $units) {
            if ($x < 100) return $under100($x);
            $h = intdiv($x, 100); $r = $x % 100;
            return trim($units[$h] . ' hundred' . ($r ? ' ' . $under100($r) : ''));
        };

        $crore = intdiv($n, 10000000); $n %= 10000000;
        $lakh  = intdiv($n, 100000);   $n %= 100000;
        $thou  = intdiv($n, 1000);     $n %= 1000;
        $rest  = $n;

        $parts = [];
        if ($crore) $parts[] = $under1000($crore) . ' crore';
        if ($lakh)  $parts[] = $under1000($lakh) . ' lakh';
        if ($thou)  $parts[] = $under1000($thou) . ' thousand';
        if ($rest)  $parts[] = $under1000($rest);

        return ucfirst(trim(implode(' ', $parts))) . ' rupees only';
    }
}
