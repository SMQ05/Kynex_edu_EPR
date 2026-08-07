<?php

declare(strict_types=1);

namespace App\Filament\StudentPortal\Pages;

use App\Filament\StudentPortal\Concerns\ResolvesCurrentStudent;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\HomeworkSubmission;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Assignments for the signed-in student, in three states:
 *
 *   to do     — nothing submitted yet (overdue ones are flagged)
 *   submitted — handed in, waiting on the teacher
 *   graded    — marked, with the teacher's feedback
 *
 * Students can hand work in from here. Submitting is deliberately one-way:
 * once handed in the text is locked, because letting a student silently edit
 * after the due date would make the teacher's view untrustworthy.
 */
class MyAssignments extends Page
{
    use ResolvesCurrentStudent;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'My Assignments';

    protected string $view = 'filament.student-portal.pages.my-assignments';

    /** Assignment currently being written against. */
    public ?string $submittingFor = null;

    public string $submissionText = '';

    public function getHeading(): string
    {
        return 'My Assignments';
    }

    public function getSubheading(): ?string
    {
        $counts = $this->counts;

        return sprintf(
            '%d to do · %d awaiting marking · %d graded',
            $counts['todo'],
            $counts['submitted'],
            $counts['graded'],
        );
    }

    /** Every assignment set for this student's class and section. */
    protected function assignmentsQuery()
    {
        return HomeworkAssignment::query()
            ->with(['subject', 'teacher'])
            ->where('class_id', $this->studentClassId())
            ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $this->studentSectionId()));
    }

    /** This student's submissions, keyed by assignment id. */
    #[Computed]
    public function submissions(): Collection
    {
        return HomeworkSubmission::where('student_id', $this->studentId())
            ->get()
            ->keyBy('homework_id');
    }

    #[Computed]
    public function todo(): Collection
    {
        $submitted = $this->submissions->keys();

        return $this->assignmentsQuery()
            ->whereNotIn('id', $submitted->all() ?: ['__none__'])
            ->orderBy('due_date')
            ->get();
    }

    #[Computed]
    public function submitted(): Collection
    {
        $ids = $this->submissions
            ->filter(fn ($s) => $s->marks_obtained === null && $s->grade === null)
            ->keys();

        return $ids->isEmpty()
            ? collect()
            : $this->assignmentsQuery()->whereIn('id', $ids->all())->orderByDesc('due_date')->get();
    }

    #[Computed]
    public function graded(): Collection
    {
        $ids = $this->submissions
            ->filter(fn ($s) => $s->marks_obtained !== null || $s->grade !== null)
            ->keys();

        return $ids->isEmpty()
            ? collect()
            : $this->assignmentsQuery()->whereIn('id', $ids->all())->orderByDesc('due_date')->get();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'todo' => $this->todo->count(),
            'submitted' => $this->submitted->count(),
            'graded' => $this->graded->count(),
        ];
    }

    public function startSubmission(string $assignmentId): void
    {
        $this->submittingFor = $assignmentId;
        $this->submissionText = '';
    }

    public function cancelSubmission(): void
    {
        $this->submittingFor = null;
        $this->submissionText = '';
    }

    /**
     * Hand in the open assignment.
     *
     * Re-resolves the assignment through assignmentsQuery() so a tampered
     * submittingFor cannot post work against another class's assignment, and
     * refuses if a submission already exists.
     */
    public function submit(): void
    {
        $text = trim($this->submissionText);

        if ($this->submittingFor === null || $text === '') {
            return;
        }

        $assignment = $this->assignmentsQuery()->find($this->submittingFor);

        if (! $assignment) {
            Notification::make()->title('That assignment is not available to you.')->danger()->send();
            $this->cancelSubmission();

            return;
        }

        $already = HomeworkSubmission::where('student_id', $this->studentId())
            ->where('homework_id', $assignment->id)
            ->exists();

        if ($already) {
            Notification::make()->title('You have already submitted this one.')->warning()->send();
            $this->cancelSubmission();
            unset($this->submissions, $this->todo, $this->submitted, $this->graded, $this->counts);

            return;
        }

        HomeworkSubmission::create([
            'id' => (string) Str::ulid(),
            'homework_id' => $assignment->id,
            'student_id' => $this->studentId(),
            'submission_text' => $text,
            'submitted_at' => now(),
            'total_marks' => $assignment->total_marks,
        ]);

        Notification::make()
            ->title('Submitted')
            ->body('"' . Str::limit($assignment->title, 40) . '" has been handed in.')
            ->success()
            ->send();

        $this->cancelSubmission();
        unset($this->submissions, $this->todo, $this->submitted, $this->graded, $this->counts);
    }

    public function submissionFor(string $assignmentId): ?HomeworkSubmission
    {
        return $this->submissions->get($assignmentId);
    }
}
