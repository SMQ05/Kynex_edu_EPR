<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant\AnnualResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ResultCardController extends Controller
{
    public function download(string $resultId): Response
    {
        $result = AnnualResult::with([
            'student',
            'schoolClass',
            'section',
            'academicYear',
        ])->findOrFail($resultId);

        $tenant = function_exists('tenant') ? tenant() : null;

        $pdf = Pdf::loadView('result-cards.pdf', [
            'result' => $result,
            'tenant' => $tenant,
        ])->setPaper('a4');

        $studentName = preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            ($result->student?->first_name ?? 'Student') . '_' . ($result->student?->last_name ?? ''),
        );

        return $pdf->download("ResultCard_{$studentName}_{$result->academicYear?->name}.pdf");
    }
}
