<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Enums\AttendanceStatus;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\Student;

/**
 * Read tool: attendance summary for a student over the last N days.
 */
class AttendanceSummaryTool extends AiTool
{
    public function name(): string
    {
        return 'student_attendance_summary';
    }

    public function description(): string
    {
        return 'Get a student\'s attendance summary (present/absent/late counts and % present) over a recent window.';
    }

    public function parameters(): array
    {
        return [
            'student' => ['type' => 'string', 'description' => 'Admission number or name'],
            'days'    => ['type' => 'integer', 'description' => 'Lookback window in days (default 30)'],
        ];
    }

    protected function requiredKeys(): array
    {
        return ['student'];
    }

    public function requiredPermission(): ?string
    {
        return 'view_attendance';
    }

    public function handle(array $args): string
    {
        $q = trim((string) ($args['student'] ?? ''));
        $days = (int) ($args['days'] ?? 30);
        $days = $days > 0 ? min($days, 365) : 30;

        if ($q === '') {
            return 'No student provided.';
        }

        $student = Student::query()
            ->where('admission_number', 'ilike', $q)
            ->orWhere('first_name', 'ilike', "%{$q}%")
            ->orWhere('last_name', 'ilike', "%{$q}%")
            ->first();

        if (! $student) {
            return "No student found matching \"{$q}\".";
        }

        $since = now()->subDays($days)->toDateString();
        $base = AttendanceRecord::where('student_id', $student->id)->where('date', '>=', $since);

        $total = (clone $base)->count();
        if ($total === 0) {
            return "{$student->first_name} {$student->last_name}: no attendance recorded in the last {$days} days.";
        }

        $present = (clone $base)->where('status', AttendanceStatus::Present->value)->count();
        $absent = (clone $base)->where('status', AttendanceStatus::Absent->value)->count();
        $late = (clone $base)->where('status', AttendanceStatus::Late->value)->count();
        $pct = round(($present / $total) * 100, 1);

        return sprintf(
            '%s %s (last %d days): %d records — %d present, %d absent, %d late. %.1f%% present.',
            $student->first_name,
            $student->last_name,
            $days,
            $total,
            $present,
            $absent,
            $late,
            $pct,
        );
    }
}
