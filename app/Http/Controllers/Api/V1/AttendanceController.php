<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Student;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * AttendanceController — API endpoints for attendance data.
 *
 * Supports:
 *   GET  /api/v1/attendance          — list attendance records (filter by student, class, date)
 *   GET  /api/v1/attendance/summary  — get attendance summary for a student
 *   POST /api/v1/attendance          — mark class attendance (staff only)
 */
class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
    ) {}

    /**
     * GET /api/v1/attendance
     *
     * Query params:
     *   - student_id (optional) filter by student
     *   - class_id (optional) filter by class
     *   - section_id (optional) filter by section
     *   - date (optional) exact date YYYY-MM-DD
     *   - from (optional) date range start
     *   - to (optional) date range end
     *   - status (optional) filter by status
     *   - per_page (optional, default 15)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'student_id' => ['sometimes', 'string'],
            'class_id'   => ['sometimes', 'string'],
            'section_id' => ['sometimes', 'string'],
            'date'       => ['sometimes', 'date_format:Y-m-d'],
            'from'       => ['sometimes', 'date_format:Y-m-d'],
            'to'         => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status'     => ['sometimes', 'string'],
            'per_page'   => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = AttendanceRecord::query()
            ->with(['student', 'schoolClass', 'section', 'marker']);

        // Filter by student — if user is a student, auto-scope to their records
        $user = $request->user();
        if ($user->hasRole('STUDENT')) {
            $student = Student::where('school_user_id', $user->id)->first();
            if ($student) {
                $query->where('student_id', $student->id);
            }
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        } elseif ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->input('from'));
            if ($request->filled('to')) {
                $query->whereDate('date', '<=', $request->input('to'));
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $records = $query->orderByDesc('date')
            ->orderBy('student_id')
            ->paginate($request->integer('per_page', 15));

        return AttendanceRecordResource::collection($records);
    }

    /**
     * GET /api/v1/attendance/summary
     *
     * Returns attendance summary for a student over a date range.
     *
     * Query params:
     *   - student_id (required unless user is a student)
     *   - from (required) YYYY-MM-DD
     *   - to (required) YYYY-MM-DD
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $isStudent = $user->hasRole('STUDENT');

        $request->validate([
            'student_id' => [$isStudent ? 'sometimes' : 'required', 'string'],
            'from'       => ['required', 'date_format:Y-m-d'],
            'to'         => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        if ($isStudent) {
            $student = Student::where('school_user_id', $user->id)->firstOrFail();
            $studentId = $student->id;
        } else {
            $studentId = $request->input('student_id');
        }

        $summary = $this->attendanceService->getStudentAttendanceSummary(
            $studentId,
            Carbon::parse($request->input('from')),
            Carbon::parse($request->input('to')),
        );

        return response()->json([
            'success' => true,
            'data'    => $summary,
        ]);
    }

    /**
     * POST /api/v1/attendance
     *
     * Mark attendance for a class section. Staff-only.
     *
     * Body:
     *   - class_id (required)
     *   - section_id (required)
     *   - academic_year_id (required)
     *   - date (required) YYYY-MM-DD
     *   - records (required) array of { student_id, status, remarks?, late_minutes? }
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only staff (teacher, admin) can mark attendance
        if ($user->hasRole('STUDENT') || $user->hasRole('PARENT')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to mark attendance.',
            ], 403);
        }

        $validated = $request->validate([
            'class_id'                  => ['required', 'string'],
            'section_id'                => ['required', 'string'],
            'academic_year_id'          => ['required', 'string'],
            'date'                      => ['required', 'date_format:Y-m-d'],
            'records'                   => ['required', 'array', 'min:1'],
            'records.*.student_id'      => ['required', 'string'],
            'records.*.status'          => ['required', Rule::in(['present', 'absent', 'late', 'half_day', 'excused'])],
            'records.*.remarks'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'records.*.late_minutes'    => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        // Transform records array into the format expected by AttendanceService
        $recordsMap = [];
        foreach ($validated['records'] as $record) {
            $recordsMap[$record['student_id']] = [
                'status'       => $record['status'],
                'remarks'      => $record['remarks'] ?? null,
                'late_minutes' => $record['late_minutes'] ?? null,
            ];
        }

        $result = $this->attendanceService->markClassAttendance(
            classId: $validated['class_id'],
            sectionId: $validated['section_id'],
            academicYearId: $validated['academic_year_id'],
            date: Carbon::parse($validated['date']),
            records: $recordsMap,
            markedBy: $user->id,
        );

        return response()->json([
            'success' => true,
            'data'    => $result,
            'message' => $result['already_marked']
                ? "Attendance updated — {$result['marked']} records updated."
                : "Attendance marked — {$result['marked']} records created.",
        ], $result['already_marked'] ? 200 : 201);
    }
}
