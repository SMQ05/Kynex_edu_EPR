<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ClassRoutine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TimetableController — API endpoints for class timetable/routine.
 */
class TimetableController extends Controller
{
    /**
     * GET /api/v1/timetable
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClassRoutine::query()
            ->with(['schoolClass', 'section', 'subject', 'teacher']);

        if ($request->has('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }

        if ($request->has('section_id')) {
            $query->where('section_id', $request->get('section_id'));
        }

        if ($request->has('day')) {
            $query->where('day_of_week', $request->get('day'));
        }

        $routines = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'day' => $r->day_of_week,
                'start_time' => $r->start_time,
                'end_time' => $r->end_time,
                'class' => $r->schoolClass?->name,
                'section' => $r->section?->name,
                'subject' => $r->subject?->name,
                'teacher' => $r->teacher?->name ?? 'TBD',
                'room' => $r->room_number,
            ]);

        return response()->json([
            'success' => true,
            'data' => $routines,
            'meta' => [
                'page' => 1,
                'total' => $routines->count(),
            ],
        ]);
    }
}
