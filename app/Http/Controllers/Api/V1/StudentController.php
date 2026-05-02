<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * StudentController — API endpoints for student data.
 * Full logic will be implemented in Phase 6.
 */
class StudentController extends Controller
{
    /**
     * GET /api/v1/students
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $students = Student::query()
            ->with(['schoolClass', 'section'])
            ->when($request->get('class_id'), fn ($q, $v) => $q->where('class_id', $v))
            ->when($request->get('section_id'), fn ($q, $v) => $q->where('section_id', $v))
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $students->items(),
            'meta' => [
                'page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/students/{id}
     */
    public function show(string $id): JsonResponse
    {
        $student = Student::with(['schoolClass', 'section', 'guardians', 'campus'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $student,
        ]);
    }

    /**
     * POST /api/v1/students
     */
    public function store(Request $request): JsonResponse
    {
        // Stub — full validation & creation logic in Phase 6
        return response()->json([
            'success' => false,
            'message' => 'Student creation via API will be available in Phase 6.',
        ], 501);
    }
}
