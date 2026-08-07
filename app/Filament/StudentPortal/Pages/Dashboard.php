<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\StudentFee;
use App\Models\Tenant\StudyMaterial;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class Dashboard extends Page
{
    use ResolvesCurrentStudent;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'My Dashboard';

    protected string $view = 'filament.student-portal.pages.dashboard';

    public function getHeading(): string
    {
        $student = $this->student();

        return $student ? 'Hello, ' . $student->first_name : 'My Dashboard';
    }

    public function getSubheading(): ?string
    {
        $student = $this->student();
        if (! $student) {
            return null;
        }

        return trim(sprintf(
            '%s · %s %s · Student ID %s',
            $student->schoolClass?->name ?? 'Unassigned class',
            'Section',
            $student->section?->name ?? '—',
            $student->admission_number ?? '—',
        ));
    }

    /** Headline counters for the top of the page. */
    #[Computed]
    public function stats(): array
    {
        $studentId = $this->studentId();
        $classId = $this->studentClassId();
        $sectionId = $this->studentSectionId();

        // Attendance rate across everything recorded for this student.
        $totalDays = AttendanceRecord::where('student_id', $studentId)->count();
        $present = AttendanceRecord::where('student_id', $studentId)
            ->whereIn('status', ['present', 'late'])
            ->count();
        $attendanceRate = $totalDays > 0 ? round($present / $totalDays * 100, 1) : null;

        // Assignments still owed: due in the future or overdue, with nothing
        // submitted by this student yet.
        $submittedIds = HomeworkSubmission::where('student_id', $studentId)
            ->pluck('homework_id');

        $pending = HomeworkAssignment::query()
            ->where('class_id', $classId)
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $sectionId))
            ->whereNotIn('id', $submittedIds)
            ->count();

        $latestResult = ExamResult::where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->first();

        // There is no balance column: outstanding is
        // amount - discount + fine - paid, summed over anything not settled.
        // Statuses in this schema are pending | partial | paid.
        $outstandingFees = (int) StudentFee::where('student_id', $studentId)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('COALESCE(SUM(amount_paisas - discount_paisas + fine_paisas - paid_paisas), 0) AS due')
            ->value('due');

        return [
            'attendance_rate' => $attendanceRate,
            'attendance_days' => $totalDays,
            'pending_assignments' => $pending,
            'latest_grade' => $latestResult?->grade,
            'latest_percentage' => $latestResult ? round((float) $latestResult->percentage, 1) : null,
            'outstanding_fees' => max(0, $outstandingFees),
            'lecture_count' => StudyMaterial::query()
                ->where('is_published', true)
                ->where('class_id', $classId)
                ->count(),
        ];
    }

    /** Assignments due next, soonest first. */
    #[Computed]
    public function upcomingAssignments(): Collection
    {
        $submittedIds = HomeworkSubmission::where('student_id', $this->studentId())
            ->pluck('homework_id');

        return HomeworkAssignment::query()
            ->with('subject')
            ->where('class_id', $this->studentClassId())
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $this->studentSectionId()))
            ->whereNotIn('id', $submittedIds)
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    /** Exams scheduled from today onward. */
    #[Computed]
    public function upcomingExams(): Collection
    {
        return ExamSchedule::query()
            ->with(['exam', 'subject'])
            ->where('class_id', $this->studentClassId())
            ->whereDate('exam_date', '>=', Carbon::today())
            ->orderBy('exam_date')
            ->limit(5)
            ->get();
    }

    /** Most recently published lectures for this student's class. */
    #[Computed]
    public function recentLectures(): Collection
    {
        return StudyMaterial::query()
            ->with('subject')
            ->where('is_published', true)
            ->where('class_id', $this->studentClassId())
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();
    }
}
