<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\HomeworkAssignmentResource;
use App\Http\Resources\Api\V1\HomeworkSubmissionResource;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * HomeworkController — API endpoints for homework/assignments.
 *
 * Supports:
 *   GET  /api/v1/homework             — list homework assignments
 *   GET  /api/v1/homework/{id}        — get single assignment detail
 *   POST /api/v1/homework/{id}/submit — submit homework (student)
 */
class HomeworkController extends Controller
{
    /**
     * GET /api/v1/homework
     *
     * Query params:
     *   - class_id (optional)
     *   - section_id (optional)
     *   - subject_id (optional)
     *   - status (optional) upcoming|overdue|all (default: all)
     *   - per_page (optional, default 15)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'class_id'   => ['sometimes', 'string'],
            'section_id' => ['sometimes', 'string'],
            'subject_id' => ['sometimes', 'string'],
            'status'     => ['sometimes', 'string', 'in:upcoming,overdue,all'],
            'per_page'   => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $query = HomeworkAssignment::query()
            ->with(['schoolClass', 'section', 'subject', 'teacher']);

        // Auto-scope student users to their class/section
        if ($user->hasRole('STUDENT')) {
            $student = Student::where('school_user_id', $user->id)->first();
            if ($student) {
                $query->where('class_id', $student->class_id)
                    ->where('section_id', $student->section_id);

                // Eager load the student's submission for each assignment
                $query->with(['submissions' => function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                }]);
            }
        } elseif ($user->hasRole('PARENT')) {
            // Guardians see homework for their linked students' classes
            $students = Student::whereHas('guardians', function ($q) use ($user) {
                $q->where('school_user_id', $user->id);
            })->get();

            if ($students->isNotEmpty()) {
                $query->where(function ($q) use ($students) {
                    foreach ($students as $student) {
                        $q->orWhere(function ($sq) use ($student) {
                            $sq->where('class_id', $student->class_id)
                                ->where('section_id', $student->section_id);
                        });
                    }
                });
            }
        } else {
            // Staff — filter by class/section if provided
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->input('class_id'));
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->input('section_id'));
            }
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        // Status filter
        $status = $request->input('status', 'all');
        if ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'overdue') {
            $query->overdue();
        }

        $homework = $query->orderByDesc('due_date')
            ->paginate($request->integer('per_page', 15));

        return HomeworkAssignmentResource::collection($homework);
    }

    /**
     * GET /api/v1/homework/{id}
     *
     * Retrieve a single homework assignment with submissions (for teachers)
     * or with the student's own submission.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = HomeworkAssignment::query()
            ->with(['schoolClass', 'section', 'subject', 'teacher']);

        // Load appropriate submissions based on role
        if ($user->hasRole('STUDENT')) {
            $student = Student::where('school_user_id', $user->id)->first();
            if ($student) {
                $query->with(['submissions' => function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                }]);
            }
        } else {
            // Staff/teachers see all submissions
            $query->with(['submissions.student']);
        }

        $homework = $query->findOrFail($id);

        // For student response, attach mySubmission
        $data = (new HomeworkAssignmentResource($homework))->toArray($request);

        if ($user->hasRole('STUDENT') && $student) {
            $mySubmission = $homework->submissions->first();
            $data['my_submission'] = $mySubmission
                ? (new HomeworkSubmissionResource($mySubmission))->toArray($request)
                : null;
        }

        if (! $user->hasRole('STUDENT')) {
            $data['submissions'] = HomeworkSubmissionResource::collection($homework->submissions)->toArray($request);
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * POST /api/v1/homework/{id}/submit
     *
     * Submit homework — student only.
     *
     * Body:
     *   - submission_text (optional) text content
     *   - attachment (optional) file upload
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('STUDENT')) {
            return response()->json([
                'success' => false,
                'message' => 'Only students can submit homework.',
            ], 403);
        }

        $student = Student::where('school_user_id', $user->id)->firstOrFail();
        $homework = HomeworkAssignment::findOrFail($id);

        // Verify student belongs to the same class/section
        if ($homework->class_id !== $student->class_id || $homework->section_id !== $student->section_id) {
            return response()->json([
                'success' => false,
                'message' => 'This homework is not assigned to your class/section.',
            ], 403);
        }

        $validated = $request->validate([
            'submission_text' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'attachment'      => ['sometimes', 'nullable', 'file', 'max:10240'], // 10MB max
        ]);

        // Check for existing submission
        $existingSubmission = HomeworkSubmission::where('homework_id', $homework->id)
            ->where('student_id', $student->id)
            ->first();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store(
                'homework-submissions/' . $homework->id,
                'public'
            );
        }

        $submissionData = [
            'submission_text' => $validated['submission_text'] ?? null,
            'submitted_at'    => Carbon::now(),
        ];

        if ($attachmentPath) {
            $submissionData['attachment_path'] = $attachmentPath;
        }

        if ($existingSubmission) {
            $existingSubmission->update($submissionData);
            $submission = $existingSubmission->fresh();
            $message = 'Homework submission updated successfully.';
            $statusCode = 200;
        } else {
            $submission = HomeworkSubmission::create(array_merge($submissionData, [
                'homework_id' => $homework->id,
                'student_id'  => $student->id,
            ]));
            $message = 'Homework submitted successfully.';
            $statusCode = 201;
        }

        return response()->json([
            'success' => true,
            'data'    => new HomeworkSubmissionResource($submission),
            'message' => $message,
        ], $statusCode);
    }
}
