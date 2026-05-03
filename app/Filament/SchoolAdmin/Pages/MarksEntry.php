<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Enums\AssignmentType;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamSchedule;
use App\Models\SchoolUser;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Models\Tenant\Subject;
use App\Services\ApprovalService;
use App\Services\ExamService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

/**
 * Unified marks entry — covers Exam marks (via ExamSchedule/ExamMark) plus
 * Homework / Class Assignment / Class Test marks (via HomeworkSubmission).
 * Teachers see only assessments tied to classes they teach.
 */
class MarksEntry extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'enter_marks';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Marks Entry';

    protected string $view = 'filament.school-admin.pages.marks-entry';

    /**
     * Marks Entry is a teacher-facing workflow. Hide from school admins and
     * institute heads — they review marks via Reports / Approval Queue, not
     * by entering them. SaaS admins also don't see this page.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }

        $role = (string) ($user->active_role ?? $user->roles->first()?->name ?? '');
        return in_array($role, ['TEACHER', 'EXAM_ADMIN'], true);
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    /** exam | homework | class_assignment | class_test */
    public string $assessment_kind = 'exam';

    // ── Exam-flow filters ──────────────────────────────────────────
    public ?string $exam_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;
    public ?string $subject_id = null;
    public ?string $exam_schedule_id = null;

    // ── Homework-flow filter ───────────────────────────────────────
    public ?string $homework_id = null;

    // ── Loaded marks rows ──────────────────────────────────────────
    public ?int $full_marks = null;
    public ?int $pass_marks = null;
    public array $marks = [];
    public string $context_label = '';

    public function mount(): void
    {
        // Default empty.
    }

    // ── Derived helpers ────────────────────────────────────────────

    public function isExamFlow(): bool
    {
        return $this->assessment_kind === 'exam';
    }

    public function teacherHasNoAssignments(): bool
    {
        return $this->actingAsTeacher() && $this->teacherAllowedTuples()->isEmpty();
    }

    protected function actingAsTeacher(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }

        $active = $user->active_role ?? $user->roles->first()?->name;

        return $active === 'TEACHER';
    }

    protected function teacherAllowedTuples(): Collection
    {
        return ClassSubject::query()
            ->where('teacher_id', auth()->guard('school_users')->id())
            ->get(['class_id', 'section_id', 'subject_id']);
    }

    // ── Computed dropdowns ─────────────────────────────────────────

    #[Computed]
    public function exams(): Collection
    {
        if (! $this->isExamFlow()) {
            return collect();
        }

        // Show every exam in the tenant. Teachers still only see classes
        // they're assigned to (in classOptions), and the load-students
        // step verifies a matching ExamSchedule exists. Filtering exams
        // by "must already have a schedule for one of my classes" gave a
        // confusing empty dropdown when admin created exams but hadn't
        // scheduled them yet.
        return Exam::query()
            ->with('academicYear')
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function classOptions(): array
    {
        if (! $this->isExamFlow()) {
            return [];
        }

        $query = SchoolClass::query()->orderBy('sort_order')->orderBy('name');

        // Don't filter classes by "must have a schedule for this exam" —
        // when an admin first opens this page after creating an exam,
        // there are no schedules yet. Showing all classes lets them pick
        // and the load step will tell them if a schedule is missing.

        if ($this->actingAsTeacher()) {
            $ids = $this->teacherAllowedTuples()->pluck('class_id')->unique();
            $query->whereIn('id', $ids);
        }

        return $query->pluck('name', 'id')->all();
    }

    #[Computed]
    public function sectionOptions(): array
    {
        if (! $this->isExamFlow() || ! $this->class_id) {
            return [];
        }

        $query = Section::where('class_id', $this->class_id)->orderBy('name');

        if ($this->actingAsTeacher()) {
            $allowed = $this->teacherAllowedTuples()
                ->where('class_id', $this->class_id)
                ->pluck('section_id')
                ->filter()
                ->unique();

            if ($allowed->isNotEmpty()) {
                $query->whereIn('id', $allowed);
            }
        }

        return $query->pluck('name', 'id')->all();
    }

    #[Computed]
    public function subjectOptions(): array
    {
        if (! $this->isExamFlow() || ! $this->class_id) {
            return [];
        }

        $query = Subject::query()
            ->whereHas('classSubjects', fn ($q) => $q->where('class_id', $this->class_id))
            ->orderBy('name');

        if ($this->actingAsTeacher()) {
            $subjectIds = $this->teacherAllowedTuples()
                ->where('class_id', $this->class_id)
                ->pluck('subject_id')
                ->unique();
            $query->whereIn('id', $subjectIds);
        }

        return $query->pluck('name', 'id')->all();
    }

    #[Computed]
    public function homeworkOptions(): array
    {
        if ($this->isExamFlow()) {
            return [];
        }

        $query = HomeworkAssignment::query()
            ->where('type', $this->assessment_kind)
            ->whereNotNull('total_marks')
            ->with(['schoolClass', 'section', 'subject']);

        if ($this->actingAsTeacher()) {
            $query->where('teacher_id', auth()->guard('school_users')->id());
        }

        return $query->orderByDesc('due_date')
            ->orderByDesc('created_at')
            ->get()
            ->mapWithKeys(function (HomeworkAssignment $h) {
                $section = $h->section?->name ? " / {$h->section->name}" : '';
                $due = $h->due_date?->format('d M') ?? 'no due date';
                return [
                    $h->id => "{$h->title} — {$h->schoolClass?->name}{$section} · {$h->subject?->name} · /{$h->total_marks} · due {$due}",
                ];
            })
            ->all();
    }

    public function assessmentTypeOptions(): array
    {
        return [
            'exam'             => 'Exam (Quarterly / Mid-Term / Semi-Final / Final)',
            'homework'         => 'Homework',
            'class_assignment' => 'Class Assignment',
            'class_test'       => 'Class Test',
        ];
    }

    // ── Reactive resets ────────────────────────────────────────────

    public function updatedAssessmentKind(): void
    {
        $this->exam_id = null;
        $this->class_id = null;
        $this->section_id = null;
        $this->subject_id = null;
        $this->exam_schedule_id = null;
        $this->homework_id = null;
        $this->marks = [];
        $this->full_marks = null;
        $this->pass_marks = null;
        $this->context_label = '';
    }

    public function updatedExamId(): void
    {
        $this->class_id = null;
        $this->section_id = null;
        $this->subject_id = null;
        $this->exam_schedule_id = null;
        $this->marks = [];
    }

    public function updatedClassId(): void
    {
        $this->section_id = null;
        $this->subject_id = null;
        $this->exam_schedule_id = null;
        $this->marks = [];
    }

    public function updatedHomeworkId(): void
    {
        $this->marks = [];
    }

    // ── Loaders ────────────────────────────────────────────────────

    public function loadStudents(): void
    {
        if ($this->isExamFlow()) {
            $this->loadStudentsForExam();
        } else {
            $this->loadStudentsForHomework();
        }
    }

    protected function loadStudentsForExam(): void
    {
        if (! $this->exam_id || ! $this->class_id || ! $this->subject_id) {
            Notification::make()
                ->title('Please select Exam, Class, and Subject')
                ->warning()
                ->send();
            return;
        }

        $schedule = ExamSchedule::where('exam_id', $this->exam_id)
            ->where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->where('subject_id', $this->subject_id)
            ->first();

        if (! $schedule) {
            Notification::make()
                ->title('No exam schedule found for this combination')
                ->body('Ask an admin to create a schedule under Examinations → Exam Schedules.')
                ->danger()
                ->send();
            return;
        }

        $this->exam_schedule_id = $schedule->id;
        $this->full_marks = $schedule->full_marks;
        $this->pass_marks = $schedule->pass_marks;

        $exam = Exam::find($this->exam_id);
        $class = SchoolClass::find($this->class_id);
        $subject = Subject::find($this->subject_id);
        $this->context_label = sprintf(
            '%s · %s · %s',
            $exam?->name ?? 'Exam',
            $class?->name ?? 'Class',
            $subject?->name ?? 'Subject',
        );

        $students = Student::where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->enrolled()
            ->orderBy('roll_number')
            ->get();

        $existing = ExamMark::where('exam_schedule_id', $schedule->id)
            ->get()
            ->keyBy('student_id');

        $this->marks = $students->map(fn (Student $s) => [
            'student_id'     => $s->id,
            'student_name'   => $s->full_name,
            'roll_number'    => $s->roll_number,
            'marks_obtained' => $existing[$s->id]?->marks_obtained ?? '',
            'is_absent'      => (bool) ($existing[$s->id]?->is_absent ?? false),
            'remarks'        => $existing[$s->id]?->remarks ?? '',
        ])->toArray();
    }

    protected function loadStudentsForHomework(): void
    {
        if (! $this->homework_id) {
            Notification::make()
                ->title('Please select an assignment')
                ->warning()
                ->send();
            return;
        }

        $homework = HomeworkAssignment::with(['schoolClass', 'section', 'subject'])
            ->find($this->homework_id);

        if (! $homework) {
            Notification::make()->title('Assignment not found')->danger()->send();
            return;
        }

        if (! $homework->total_marks) {
            Notification::make()
                ->title('This assignment has no Total Marks set')
                ->body('Edit the assignment and set Total Marks before grading.')
                ->warning()
                ->send();
            return;
        }

        $this->full_marks = (int) $homework->total_marks;
        $this->pass_marks = (int) max(1, round($homework->total_marks * 0.4));

        $section = $homework->section?->name ? " / {$homework->section->name}" : '';
        $this->context_label = sprintf(
            '%s · %s%s · %s · /%d',
            $homework->title,
            $homework->schoolClass?->name ?? '',
            $section,
            $homework->subject?->name ?? '',
            $homework->total_marks,
        );

        $students = Student::where('class_id', $homework->class_id)
            ->when($homework->section_id, fn ($q) => $q->where('section_id', $homework->section_id))
            ->enrolled()
            ->orderBy('roll_number')
            ->get();

        $existing = HomeworkSubmission::where('homework_id', $homework->id)
            ->get()
            ->keyBy('student_id');

        $this->marks = $students->map(fn (Student $s) => [
            'student_id'     => $s->id,
            'student_name'   => $s->full_name,
            'roll_number'    => $s->roll_number,
            'marks_obtained' => $existing[$s->id]?->marks_obtained ?? '',
            'is_absent'      => false,
            'remarks'        => $existing[$s->id]?->feedback ?? '',
        ])->toArray();
    }

    // Backwards-compat alias used by older blade events.
    public function loadStudentsForMarks(): void
    {
        $this->loadStudents();
    }

    // ── Save ───────────────────────────────────────────────────────

    public function saveMarks(): void
    {
        if (empty($this->marks)) {
            Notification::make()->title('No marks data to save')->warning()->send();
            return;
        }

        $count = $this->isExamFlow() ? $this->saveExamMarks() : $this->saveHomeworkMarks();

        Notification::make()
            ->title("Marks saved for {$count} students")
            ->success()
            ->send();
    }

    protected function saveExamMarks(): int
    {
        if (! $this->exam_schedule_id) {
            Notification::make()->title('Load students first')->warning()->send();
            return 0;
        }

        foreach ($this->marks as $entry) {
            if (! $entry['is_absent'] && $entry['marks_obtained'] !== '' && $entry['marks_obtained'] !== null) {
                $obtained = (float) $entry['marks_obtained'];
                if ($obtained < 0 || $obtained > $this->full_marks) {
                    Notification::make()
                        ->title("Invalid marks for {$entry['student_name']}: must be between 0 and {$this->full_marks}")
                        ->danger()
                        ->send();
                    return 0;
                }
            }
        }

        $user = auth()->guard('school_users')->user();
        $bypassApproval = $user?->can('bypass_approvals') ?? false;

        $existingMarks = ExamMark::where('exam_schedule_id', $this->exam_schedule_id)
            ->get()
            ->keyBy('student_id');

        $directRows = [];
        $approvalsSubmitted = 0;

        foreach ($this->marks as $entry) {
            $studentId = $entry['student_id'];
            $newValue = ($entry['is_absent'] || $entry['marks_obtained'] === '' || $entry['marks_obtained'] === null)
                ? null
                : (float) $entry['marks_obtained'];

            $existing = $existingMarks->get($studentId);

            // First-time entry → save directly. No approval needed.
            if (! $existing) {
                $directRows[] = [
                    'student_id'     => $studentId,
                    'marks_obtained' => $newValue,
                    'is_absent'      => (bool) $entry['is_absent'],
                    'remarks'        => $entry['remarks'] ?? null,
                ];
                continue;
            }

            $oldValue = $existing->marks_obtained === null ? null : (float) $existing->marks_obtained;
            $unchanged = $oldValue === $newValue
                && (bool) $existing->is_absent === (bool) $entry['is_absent']
                && ($existing->remarks ?? '') === ($entry['remarks'] ?? '');

            if ($unchanged) {
                continue;
            }

            // Editing an existing mark: privileged users save directly,
            // everyone else submits an approval request per changed row.
            if ($bypassApproval) {
                $directRows[] = [
                    'student_id'     => $studentId,
                    'marks_obtained' => $newValue,
                    'is_absent'      => (bool) $entry['is_absent'],
                    'remarks'        => $entry['remarks'] ?? null,
                ];
                continue;
            }

            $approval = app(ApprovalService::class)->submit(
                requestedBy: $user,
                actionType: 'exam_mark_change',
                subject: $existing,
                payload: [
                    'exam_schedule_id' => $this->exam_schedule_id,
                    'student_id'       => $studentId,
                    'student_name'     => $entry['student_name'],
                    'old_marks'        => $oldValue,
                    'new_marks'        => $newValue,
                    'old_is_absent'    => (bool) $existing->is_absent,
                    'new_is_absent'    => (bool) $entry['is_absent'],
                    'old_remarks'      => $existing->remarks,
                    'new_remarks'      => $entry['remarks'] ?? null,
                    'reason'           => 'Mark edit submitted via Marks Entry page',
                ],
            );

            $this->notifyMarkEditReviewers($approval, $entry['student_name'], $oldValue, $newValue, $user, $existing);
            $approvalsSubmitted++;
        }

        $directCount = 0;
        if (! empty($directRows)) {
            $directCount = app(ExamService::class)->saveMarks(
                $this->exam_schedule_id,
                $directRows,
                $user?->id,
            );
        }

        if ($approvalsSubmitted > 0) {
            Notification::make()
                ->title("{$approvalsSubmitted} mark edits sent for approval")
                ->body('Edits to existing marks require approval from the institute head / exam admin.')
                ->warning()
                ->send();
        }

        return $directCount;
    }

    protected function saveHomeworkMarks(): int
    {
        $homework = HomeworkAssignment::find($this->homework_id);
        if (! $homework) {
            return 0;
        }

        foreach ($this->marks as $entry) {
            if ($entry['marks_obtained'] === '' || $entry['marks_obtained'] === null) {
                continue;
            }
            $obtained = (float) $entry['marks_obtained'];
            if ($obtained < 0 || $obtained > $homework->total_marks) {
                Notification::make()
                    ->title("Invalid marks for {$entry['student_name']}: must be between 0 and {$homework->total_marks}")
                    ->danger()
                    ->send();
                return 0;
            }
        }

        $count = 0;
        $now = now();
        $teacherId = auth()->guard('school_users')->id();

        foreach ($this->marks as $entry) {
            if ($entry['marks_obtained'] === '' || $entry['marks_obtained'] === null) {
                continue;
            }

            $submission = HomeworkSubmission::firstOrNew([
                'homework_id' => $homework->id,
                'student_id'  => $entry['student_id'],
            ]);

            $submission->marks_obtained = (float) $entry['marks_obtained'];
            $submission->total_marks = $homework->total_marks;
            $submission->feedback = $entry['remarks'] ?: null;
            $submission->graded_by = $teacherId;
            $submission->graded_at = $now;
            $submission->submitted_at ??= $now;
            $submission->save();

            $count++;
        }

        return $count;
    }

    /**
     * Send an in-app notification to every reviewer who needs to know
     * about a marks edit: the school admin team, the teacher who originally
     * entered the mark, the institute head, and the exam admin.
     */
    protected function notifyMarkEditReviewers(
        $approval,
        string $studentName,
        ?float $oldValue,
        ?float $newValue,
        ?SchoolUser $requester,
        $existingMark,
    ): void {
        $body = sprintf(
            '%s submitted a mark edit for %s: %s → %s. Approval required.',
            $requester?->name ?? 'A teacher',
            $studentName,
            $oldValue ?? '—',
            $newValue ?? '—',
        );

        $notifyRoleNames = ['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'EXAM_ADMIN'];

        $recipientIds = SchoolUser::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $notifyRoleNames))
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        // Plus the teacher who originally entered the mark, if any.
        if ($existingMark?->entered_by) {
            $recipientIds[] = $existingMark->entered_by;
        }

        $recipientIds = array_values(array_unique(array_filter($recipientIds, fn ($id) => $id !== $requester?->id)));

        foreach ($recipientIds as $recipientId) {
            InAppNotification::create([
                'user_id' => $recipientId,
                'title'   => 'Mark edit pending approval',
                'body'    => $body,
                'type'    => 'warning',
            ]);
        }
    }

    public function calculateResults(): void
    {
        if (! $this->isExamFlow() || ! $this->exam_id) {
            Notification::make()
                ->title('Switch to Exam flow and pick an exam first')
                ->warning()
                ->send();
            return;
        }

        $count = app(ExamService::class)->calculateResults($this->exam_id, $this->class_id);

        Notification::make()
            ->title("Results calculated for {$count} students")
            ->success()
            ->send();
    }
}
