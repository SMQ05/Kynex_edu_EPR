<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Report card: each completed exam with its overall grade and rank, plus the
 * per-subject marks that produced it, and an attendance summary.
 */
class MyResults extends Page
{
    use ResolvesCurrentStudent;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'My Results';

    protected string $view = 'filament.student-portal.pages.my-results';

    public function getHeading(): string
    {
        return 'My Results';
    }

    /** Completed exams with an aggregated result, newest first. */
    #[Computed]
    public function results(): Collection
    {
        return ExamResult::query()
            ->with('exam')
            ->where('student_id', $this->studentId())
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Per-subject marks for every exam, grouped by exam id.
     *
     * exam_marks joins to exam_schedules (not exams) via the `schedule`
     * relation, so subject and full marks come from the schedule row.
     */
    #[Computed]
    public function marksByExam(): Collection
    {
        return ExamMark::query()
            ->with(['schedule.subject', 'schedule.exam'])
            ->where('student_id', $this->studentId())
            ->get()
            ->groupBy(fn (ExamMark $m) => $m->schedule?->exam_id ?? 'unknown');
    }

    /** Attendance split, used for the summary strip. */
    #[Computed]
    public function attendance(): array
    {
        $rows = AttendanceRecord::where('student_id', $this->studentId())
            ->selectRaw('status, COUNT(*) AS n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $total = (int) $rows->sum();
        $present = (int) ($rows['present'] ?? 0);
        $late = (int) ($rows['late'] ?? 0);

        return [
            'total' => $total,
            'present' => $present,
            'late' => $late,
            'absent' => (int) ($rows['absent'] ?? 0),
            'leave' => (int) ($rows['leave'] ?? 0),
            'rate' => $total > 0 ? round(($present + $late) / $total * 100, 1) : null,
        ];
    }
}
