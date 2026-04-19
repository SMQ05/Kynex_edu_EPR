<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Illuminate\Http\JsonResponse;

/**
 * FeeController — API endpoints for student fee data.
 * Full logic will be implemented in Phase 6.
 */
class FeeController extends Controller
{
    /**
     * GET /api/v1/fees/{studentId}
     */
    public function show(string $studentId): JsonResponse
    {
        $student = Student::findOrFail($studentId);

        $fees = StudentFee::where('student_id', $studentId)
            ->with(['feeMaster.feeType'])
            ->get()
            ->map(fn ($fee) => [
                'id' => $fee->id,
                'fee_type' => $fee->feeMaster?->feeType?->name ?? 'N/A',
                'amount_pkr' => number_format($fee->amount_paisas / 100, 2),
                'paid_pkr' => number_format($fee->paid_paisas / 100, 2),
                'balance_pkr' => number_format(($fee->amount_paisas - $fee->paid_paisas) / 100, 2),
                'status' => $fee->status?->value ?? 'unknown',
                'due_date' => $fee->due_date?->toDateString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'admission_no' => $student->admission_no,
                ],
                'fees' => $fees,
            ],
        ]);
    }
}
