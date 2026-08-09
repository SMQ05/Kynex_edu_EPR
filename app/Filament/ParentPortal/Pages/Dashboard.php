<?php

declare(strict_types=1);

namespace App\Filament\ParentPortal\Pages;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\OnlineExam;
use App\Models\Tenant\StudyMaterial;
use App\Models\Tenant\Syllabus;
use App\Models\Tenant\SyllabusTopic;
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

        // Upcoming assessments. Written exams and online assessments are two
        // different tables but one question for a parent — "what is my child
        // being tested on next?" — so they are merged into a single list.
        $upcomingExams = ExamSchedule::query()
            ->where('class_id', $student->class_id)
            ->when($student->section_id, fn ($q) => $q->where(function ($qq) use ($student) {
                $qq->whereNull('section_id')->orWhere('section_id', $student->section_id);
            }))
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->with(['exam', 'subject'])
            ->orderBy('exam_date')
            ->limit(6)
            ->get()
            ->map(fn (ExamSchedule $s) => [
                'kind' => 'written',
                'title' => $s->exam?->name ?? 'Examination',
                'subject' => $s->subject?->name,
                'at' => $s->exam_date,
                // start_time is cast to a datetime, so it stringifies as a full
                // timestamp — formatting it is not optional here.
                'detail' => trim(implode(' · ', array_filter([
                    $s->start_time?->format('g:i a'),
                    $s->room,
                ]))),
            ]);

        $upcomingOnline = OnlineExam::query()
            ->where('class_id', $student->class_id)
            ->when($student->section_id, fn ($q) => $q->where(function ($qq) use ($student) {
                $qq->whereNull('section_id')->orWhere('section_id', $student->section_id);
            }))
            ->whereIn('status', ['published', 'ongoing'])
            ->where('window_closes_at', '>=', now())
            ->with('subject')
            ->orderBy('window_opens_at')
            ->limit(6)
            ->get()
            ->map(fn (OnlineExam $e) => [
                'kind' => $e->window_opens_at <= now() ? 'open now' : 'online',
                'title' => $e->name,
                'subject' => $e->subject?->name,
                'at' => $e->window_opens_at,
                'detail' => $e->duration_minutes ? $e->duration_minutes . ' min · ' . $e->total_marks . ' marks' : null,
            ]);

        $upcomingExams = $upcomingExams
            ->concat($upcomingOnline)
            ->sortBy(fn (array $row) => (string) $row['at'])
            ->values()
            ->take(6);

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
            'courses'              => $this->courseProgressFor($student),
            'latestResult'         => $latestResult,
            'recentMarks'          => $recentMarks,
            'upcomingHomework'     => $upcomingHomework,
            'upcomingExams'        => $upcomingExams,
            'attendancePct'        => $attendancePct,
            'recentHomeworkMarks'  => $recentHomeworkMarks,
        ];
    }

    /** Initials for the child's avatar tile. */
    public function initialsFor(Student $student): string
    {
        $a = mb_substr((string) $student->first_name, 0, 1);
        $b = mb_substr((string) $student->last_name, 0, 1);

        return mb_strtoupper($a . $b) ?: '?';
    }

    /**
     * Outstanding fee position for one child.
     *
     * Uses StudentFee's own net_payable_paisas / balance_paisas accessors so the
     * definition of "balance" stays in the model rather than being re-derived
     * here and in the student portal separately.
     *
     * @return array{due:int,overdue:int,lines:int,nextDue:?\Illuminate\Support\Carbon}
     */
    public function feeSummaryFor(Student $student): array
    {
        $fees = \App\Models\Tenant\StudentFee::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('due_date')
            ->get();

        $due = 0;
        $overdue = 0;
        $today = now()->startOfDay();
        $nextDue = null;

        foreach ($fees as $fee) {
            $balance = max(0, $fee->balance_paisas);
            if ($balance <= 0) {
                continue;
            }
            $due += $balance;

            if ($fee->due_date && $today->greaterThan($fee->due_date)) {
                $overdue += $balance;
            } elseif ($nextDue === null && $fee->due_date) {
                $nextDue = $fee->due_date;
            }
        }

        return [
            'due' => $due,
            'overdue' => $overdue,
            'lines' => $fees->filter(fn ($f) => $f->balance_paisas > 0)->count(),
            'nextDue' => $nextDue,
        ];
    }

    /**
     * How far each of the child's courses has got through its plan.
     *
     * This is the question a parent actually has and no other screen answers:
     * not "what mark did they get" but "where is the class up to, and is there
     * material my child can go back to". Units come from the published
     * syllabus, so the figure is the school's own plan rather than an estimate.
     *
     * @return list<array{subject:string, done:int, total:int, pct:int, current:?string, lectures:int}>
     */
    public function courseProgressFor(Student $student): array
    {
        if (! $student->class_id) {
            return [];
        }

        $syllabi = Syllabus::query()
            ->where('class_id', $student->class_id)
            ->where('status', 'published')
            ->with('subject')
            ->get();

        if ($syllabi->isEmpty()) {
            return [];
        }

        $topics = SyllabusTopic::query()
            ->whereIn('syllabus_id', $syllabi->pluck('id'))
            ->get()
            ->groupBy('syllabus_id');

        $lectureCounts = StudyMaterial::query()
            ->where('class_id', $student->class_id)
            ->where('is_published', true)
            ->selectRaw('subject_id, count(*) as c')
            ->groupBy('subject_id')
            ->pluck('c', 'subject_id');

        $out = [];

        foreach ($syllabi as $syllabus) {
            $rows = $topics->get($syllabus->id) ?? collect();
            $total = $rows->count();

            if ($total === 0) {
                continue;
            }

            $done = $rows->where('status', 'completed')->count();
            $current = $rows->firstWhere('status', 'in_progress');

            $out[] = [
                'subject' => $syllabus->subject?->name ?? $syllabus->title,
                'done' => $done,
                'total' => $total,
                'pct' => (int) round($done / $total * 100),
                'current' => $current?->title,
                'lectures' => (int) ($lectureCounts[$syllabus->subject_id] ?? 0),
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['subject'], $b['subject']));

        return $out;
    }
}
