<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Jobs\NotifyAbsentParents;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AttendanceService — Handles class and student attendance operations.
 */
class AttendanceService
{
    /**
     * Mark attendance for an entire class section on a given date.
     *
     * @param  array<string, string>  $records  [student_id => status]
     * @return array{marked: int, already_marked: bool}
     */
    public function markClassAttendance(
        string $classId,
        string $sectionId,
        string $academicYearId,
        Carbon $date,
        array $records,
        string $markedBy,
    ): array {
        $alreadyMarked = $this->isAlreadyMarked($classId, $sectionId, $date);
        $marked = 0;
        $absentStudentIds = [];

        foreach ($records as $studentId => $data) {
            $status = is_array($data) ? ($data['status'] ?? 'absent') : $data;
            $remarks = is_array($data) ? ($data['remarks'] ?? null) : null;
            $lateMinutes = is_array($data) ? ($data['late_minutes'] ?? null) : null;

            $record = AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'date'       => $date->toDateString(),
                ],
                [
                    'class_id'         => $classId,
                    'section_id'       => $sectionId,
                    'academic_year_id' => $academicYearId,
                    'status'           => $status,
                    'marked_by'        => $markedBy,
                    'remarks'          => $remarks,
                    'late_minutes'     => $lateMinutes,
                ]
            );

            $marked++;

            if ($status === 'absent' || $status === AttendanceStatus::Absent->value) {
                $absentStudentIds[] = $studentId;
            }
        }

        // Dispatch notification job for absent students
        if (! empty($absentStudentIds)) {
            NotifyAbsentParents::dispatch($absentStudentIds, $date->toDateString());
        }

        return [
            'marked'         => $marked,
            'already_marked' => $alreadyMarked,
        ];
    }

    /**
     * Get attendance for a class section on a given date.
     * Students without a record return status='not_marked'.
     */
    public function getClassAttendance(
        string $classId,
        string $sectionId,
        Carbon $date,
    ): Collection {
        $students = Student::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get();

        $attendanceRecords = AttendanceRecord::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        return $students->map(function (Student $student) use ($attendanceRecords) {
            $record = $attendanceRecords->get($student->id);

            return (object) [
                'student_id'   => $student->id,
                'roll_number'  => $student->roll_number,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'status'       => $record?->status?->value ?? 'not_marked',
                'remarks'      => $record?->remarks,
                'late_minutes' => $record?->late_minutes,
                'record_id'    => $record?->id,
            ];
        });
    }

    /**
     * Get attendance summary for a student over a date range.
     *
     * @return array{total_days: int, present: int, absent: int, late: int, half_day: int, excused: int, attendance_percentage: float}
     */
    public function getStudentAttendanceSummary(
        string $studentId,
        Carbon $from,
        Carbon $to,
    ): array {
        $records = AttendanceRecord::where('student_id', $studentId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $totalDays = $records->count();
        $present = $records->where('status', AttendanceStatus::Present)->count();
        $absent = $records->where('status', AttendanceStatus::Absent)->count();
        $late = $records->where('status', AttendanceStatus::Late)->count();
        $halfDay = $records->where('status', AttendanceStatus::HalfDay)->count();
        $excused = $records->where('status', AttendanceStatus::Excused)->count();

        $attendedDays = $present + $late + ($halfDay * 0.5);
        $percentage = $totalDays > 0 ? round(($attendedDays / $totalDays) * 100, 2) : 0;

        return [
            'total_days'            => $totalDays,
            'present'               => $present,
            'absent'                => $absent,
            'late'                  => $late,
            'half_day'              => $halfDay,
            'excused'               => $excused,
            'attendance_percentage' => $percentage,
        ];
    }

    /**
     * Check if attendance has already been marked for a class on a date.
     */
    public function isAlreadyMarked(
        string $classId,
        string $sectionId,
        Carbon $date,
    ): bool {
        return AttendanceRecord::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->whereDate('date', $date)
            ->exists();
    }

    /**
     * Get monthly attendance trend for a class with month-over-month change.
     * Uses PostgreSQL LAG() window function for previous month comparison.
     *
     * @return array<int, object{month: string, attendance_pct: float, prev_month_pct: float|null, month_change: float|null}>
     */
    public function getAttendanceTrend(string $classId, string $academicYearId): array
    {
        return DB::select("
            SELECT
                month,
                attendance_pct,
                LAG(attendance_pct) OVER (ORDER BY month) as prev_month_pct,
                attendance_pct - LAG(attendance_pct) OVER (ORDER BY month) as month_change
            FROM (
                SELECT
                    DATE_TRUNC('month', date) as month,
                    ROUND(
                        100.0 * COUNT(*) FILTER (WHERE status = ?)
                        / NULLIF(COUNT(*), 0),
                    2) as attendance_pct
                FROM attendance_records
                WHERE class_id = ?
                  AND academic_year_id = ?
                GROUP BY DATE_TRUNC('month', date)
            ) monthly
            ORDER BY month
        ", [AttendanceStatus::Present->value, $classId, $academicYearId]);
    }
}
