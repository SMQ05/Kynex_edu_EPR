<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\Student;
use App\Models\Tenant\SyllabusTopic;
use App\Models\Tenant\Syllabus;
use App\Models\Tenant\Subject;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ClassRoutine;
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
    /**
     * The signed-in student, for the view.
     *
     * ResolvesCurrentStudent::student() is protected, and a Blade view runs as
     * a closure outside the class, so the banner needs a public door.
     */
    #[Computed]
    public function me(): ?\App\Models\Tenant\Student
    {
        return $this->student();
    }

    /**
     * The lesson happening right now, or the next one today.
     *
     * Reads the class timetable for this student's section. Returns null
     * outside school hours rather than guessing, so the panel can say
     * something true instead of showing a stale period.
     *
     * @return array{state:string, subject:?string, teacher:?string, room:?string, ends:?string, next:?string}|null
     */
    #[Computed]
    public function rightNow(): ?array
    {
        $classId = $this->studentClassId();

        if (! $classId) {
            return null;
        }

        $forDay = fn (string $day) => ClassRoutine::query()
            ->where('class_id', $classId)
            ->where('day_of_week', $day)
            ->when($this->studentSectionId(), fn ($q) => $q->where(fn ($qq) => $qq
                ->whereNull('section_id')
                ->orWhere('section_id', $this->studentSectionId())))
            ->with(['subject', 'teacher'])
            ->orderBy('start_time')
            ->get();

        $slots = $forDay(strtolower(now()->format('l')));
        $now = now()->format('H:i:s');

        $current = $slots->first(fn ($s) => $s->start_time?->format('H:i:s') <= $now
            && $s->end_time?->format('H:i:s') > $now);

        $next = $slots->first(fn ($s) => $s->start_time?->format('H:i:s') > $now);

        // Evenings, weekends and holidays: look ahead to the next teaching day
        // rather than going blank. A panel that says nothing after 4pm is a
        // panel a student stops opening.
        $tomorrow = null;

        if (! $current && ! $next) {
            for ($i = 1; $i <= 7; $i++) {
                $day = now()->addDays($i);
                $ahead = $forDay(strtolower($day->format('l')));

                if ($ahead->isNotEmpty()) {
                    $next = $ahead->first();
                    $tomorrow = $i === 1 ? 'tomorrow' : $day->format('l');
                    break;
                }
            }
        }

        if (! $current && ! $next) {
            return null;
        }

        $slot = $current ?? $next;

        return [
            'state' => $current ? ($slot->is_break ? 'break' : 'now') : 'next',
            'day' => $tomorrow,
            'period' => $slot->is_break ? null : $slot->period_number,
            'subject' => $slot->is_break ? $slot->break_label : $slot->subject?->name,
            'teacher' => $slot->teacher?->name,
            'room' => $slot->room_number,
            'ends' => $slot->end_time?->format('g:i a'),
            'starts' => $slot->start_time?->format('g:i a'),
            'next' => $next && $current
                ? trim(($next->is_break ? $next->break_label : $next->subject?->name) . ($next->room_number ? ' in ' . $next->room_number : ''))
                : null,
        ];
    }

    /**
     * Term average and the movement since the previous term.
     *
     * Uses the published exam_results rows, which already carry a percentage
     * and a rank, so this reports the school's own numbers rather than
     * recomputing them from marks and risking a different answer.
     *
     * @return array{percent:?float, delta:?float, exam:?string, rank:?int, outOf:?int}
     */
    #[Computed]
    public function standing(): array
    {
        $results = ExamResult::where('student_id', $this->studentId())
            ->with('exam')
            ->get()
            ->sortBy(fn (ExamResult $r) => $r->exam?->start_date ?? $r->created_at)
            ->values();

        $latest = $results->last();
        $previous = $results->count() > 1 ? $results[$results->count() - 2] : null;

        $outOf = $latest
            ? Student::where('class_id', $latest->class_id)->where('status', 'enrolled')->count()
            : null;

        return [
            'percent' => $latest?->percentage !== null ? round((float) $latest->percentage, 1) : null,
            'delta' => $latest && $previous && $previous->percentage !== null
                ? round((float) $latest->percentage - (float) $previous->percentage, 1)
                : null,
            'exam' => $latest?->exam?->name,
            'previous' => $previous?->exam?->name,
            'rank' => $latest?->rank,
            'outOf' => $outOf,
        ];
    }

    /**
     * Per-subject percentage for the most recent exam, worst first.
     *
     * Worst first because the panel exists to show where the work is needed;
     * a list that opens with the best subject buries the point.
     *
     * @return list<array{subject:string, percent:int}>
     */
    #[Computed]
    public function subjectPerformance(): array
    {
        $latest = ExamResult::where('student_id', $this->studentId())
            ->with('exam')
            ->get()
            ->sortBy(fn (ExamResult $r) => $r->exam?->start_date ?? $r->created_at)
            ->last();

        if (! $latest) {
            return [];
        }

        return ExamMark::query()
            ->where('student_id', $this->studentId())
            ->whereHas('schedule', fn ($q) => $q->where('exam_id', $latest->exam_id))
            ->with(['schedule.subject'])
            ->get()
            ->filter(fn (ExamMark $m) => $m->schedule?->subject && ($m->schedule->full_marks ?? 0) > 0
                && $m->marks_obtained !== null)
            ->map(fn (ExamMark $m) => [
                'subject' => $m->schedule->subject->name,
                'percent' => (int) round((float) $m->marks_obtained / (float) $m->schedule->full_marks * 100),
            ])
            ->sortBy('percent')
            ->values()
            ->all();
    }

    /**
     * One sentence on where this student should spend time next.
     *
     * Built from the weakest subject in the last exam and the unit that class
     * is on now, so it names something real. Says nothing when there is not
     * enough marked work — an invented "insight" is worse than a blank panel.
     *
     * @return array{subject:string, percent:int, topic:?string, lecture:?string}|null
     */
    #[Computed]
    public function coach(): ?array
    {
        $subjects = $this->subjectPerformance();

        if ($subjects === []) {
            return null;
        }

        $weakest = $subjects[0];

        if ($weakest['percent'] >= 75) {
            return null;
        }

        $subjectId = Subject::where('name', $weakest['subject'])->value('id');

        $topic = null;
        $lectureId = null;

        if ($subjectId) {
            $syllabusId = Syllabus::where('class_id', $this->studentClassId())
                ->where('subject_id', $subjectId)
                ->value('id');

            if ($syllabusId) {
                $unit = SyllabusTopic::where('syllabus_id', $syllabusId)
                    ->whereIn('status', ['in_progress', 'completed'])
                    ->orderByDesc('sort_order')
                    ->first();
                $topic = $unit?->title;
                $lectureId = $unit
                    ? StudyMaterial::where('syllabus_topic_id', $unit->id)->where('is_published', true)->value('id')
                    : null;
            }
        }

        return [
            'subject' => $weakest['subject'],
            'percent' => $weakest['percent'],
            'topic' => $topic,
            'lecture' => $lectureId,
        ];
    }

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

        // There is no balance column. StudentFee::getBalancePaisasAttribute()
        // derives it per row, but this needs a single aggregate, so the same
        // definition (amount + fine - discount - paid) is expressed in SQL.
        // Keep the two in step. Statuses here are pending | partial | paid.
        $outstandingFees = (int) StudentFee::where('student_id', $studentId)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('COALESCE(SUM(amount_paisas + fine_paisas - discount_paisas - paid_paisas), 0) AS due')
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
