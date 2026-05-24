<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExamResultResource;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * ResultController — API endpoints for examination results.
 *
 * Supports:
 *   GET /api/v1/results        — list exam results (filter by student, exam, class)
 *   GET /api/v1/results/{id}   — get a single exam result detail
 */
class ResultController extends Controller
{
    /**
     * GET /api/v1/results
     *
     * Query params:
     *   - student_id (optional, auto-scoped for students)
     *   - exam_id (optional) filter by specific exam
     *   - class_id (optional) filter by class
     *   - status (optional) passed/failed
     *   - per_page (optional, default 15)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'student_id' => ['sometimes', 'string'],
            'exam_id'    => ['sometimes', 'string'],
            'class_id'   => ['sometimes', 'string'],
            'status'     => ['sometimes', 'string'],
            'per_page'   => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ExamResult::query()
            ->with(['exam', 'student', 'schoolClass'])
            ->where('is_published', true);

        // Auto-scope student users to their own results
        $user = $request->user();
        if ($user->hasRole('STUDENT')) {
            $student = Student::where('school_user_id', $user->id)->first();
            if ($student) {
                $query->where('student_id', $student->id);
            }
        } elseif ($user->hasRole('PARENT')) {
            // Guardians see results for their linked students
            $studentIds = Student::whereHas('guardians', function ($q) use ($user) {
                $q->where('school_user_id', $user->id);
            })->pluck('id');
            $query->whereIn('student_id', $studentIds);

            if ($request->filled('student_id')) {
                $query->where('student_id', $request->input('student_id'));
            }
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $results = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return ExamResultResource::collection($results);
    }

    /**
     * GET /api/v1/results/{id}
     *
     * Retrieve a single exam result with full details.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $result = ExamResult::with(['exam', 'student', 'schoolClass'])
            ->where('is_published', true)
            ->findOrFail($id);

        // Authorization: students can only see their own results
        $user = $request->user();
        if ($user->hasRole('STUDENT')) {
            $student = Student::where('school_user_id', $user->id)->first();
            if (! $student || $result->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view your own results.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => new ExamResultResource($result),
        ]);
    }
}
