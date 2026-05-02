<?php

declare(strict_types=1);

namespace App\Filament\ParentPortal\Pages;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\Student;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class Dashboard extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'My Children';

    protected string $view = 'filament.parent-portal.pages.dashboard';

    public function getHeading(): string
    {
        return 'My Children';
    }

    /**
     * Resolve all Student rows whose primary or secondary guardian is the
     * logged-in school_user. Match is by guardian.school_user_id OR by
     * guardian.email matching the user's email (covers self-signup before
     * the link is fully wired).
     */
    #[Computed]
    public function children(): Collection
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return collect();
        }

        return Student::query()
            ->whereHas('guardians', function ($q) use ($user) {
                $q->where('school_user_id', $user->id)
                  ->orWhere('email', $user->email);
            })
            ->with(['schoolClass', 'section', 'campus', 'academicYear'])
            ->orderBy('first_name')
            ->get();
    }

    public function summaryFor(Student $student): array
    {
        // Latest exam result
        $latestResult = ExamResult::where('student_id', $student->id)
            ->with('exam')
            ->orderByDesc('created_at')
            ->first();

        // Recent exam marks (last 10)
        $recentMarks = ExamMark::where('student_id', $student->id)
            ->with(['schedule.exam', 'schedule.subject'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Upcoming homework due in the student's class
        $upcomingHomework = HomeworkAssignment::query()
            ->where('class_id', $student->class_id)
            ->when($student->section_id, fn ($q) => $q->where(function ($qq) use ($student) {
                $qq->whereNull('section_id')->orWhere('section_id', $student->section_id);
            }))
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Upcoming exams in the student's class
        $upcomingExams = ExamSchedule::query()
            ->where('class_id', $student->class_id)
            ->when($student->section_id, fn ($q) => $q->where(function ($qq) use ($student) {
                $qq->whereNull('section_id')->orWhere('section_id', $student->section_id);
            }))
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->with(['exam', 'subject'])
            ->orderBy('exam_date')
            ->limit(5)
            ->get();

        // Attendance %
        $attRows = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')->all();
        $total = array_sum($attRows);
        $present = (int) ($attRows['present'] ?? 0) + (int) ($attRows['excused'] ?? 0);
        $attendancePct = $total > 0 ? round($present * 100 / $total, 1) : null;

        // Recent homework grades
        $recentHomeworkMarks = HomeworkSubmission::query()
            ->where('student_id', $student->id)
            ->whereNotNull('marks_obtained')
            ->with(['homework.subject'])
            ->orderByDesc('graded_at')
            ->limit(5)
            ->get();

        return [
            'latestResult'         => $latestResult,
            'recentMarks'          => $recentMarks,
            'upcomingHomework'     => $upcomingHomework,
            'upcomingExams'        => $upcomingExams,
            'attendancePct'        => $attendancePct,
            'recentHomeworkMarks'  => $recentHomeworkMarks,
        ];
    }
}
