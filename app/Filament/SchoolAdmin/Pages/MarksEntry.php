<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\Student;
use App\Services\ExamService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class MarksEntry extends Page implements HasForms
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'enter_marks';

    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Marks Entry';

    protected string $view = 'filament.school-admin.pages.marks-entry';

    // ── Filter Properties ──────────────────────────────────────────

    public ?string $exam_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;
    public ?string $subject_id = null;

    // ── Data ───────────────────────────────────────────────────────

    public ?string $exam_schedule_id = null;
    public ?int $full_marks = null;
    public ?int $pass_marks = null;
    public array $marks = [];

    public function mount(): void
    {
        // Default empty
    }

    #[Computed]
    public function exams(): Collection
    {
        return Exam::orderByDesc('created_at')->get();
    }

    #[Computed]
    public function schedules(): Collection
    {
        if (! $this->exam_id) {
            return collect();
        }

        $query = ExamSchedule::where('exam_id', $this->exam_id)
            ->with(['schoolClass', 'section', 'subject']);

        return $query->get();
    }

    public function updatedExamId(): void
    {
        $this->class_id = null;
        $this->section_id = null;
        $this->subject_id = null;
        $this->exam_schedule_id = null;
        $this->marks = [];
    }

    public function loadStudentsForMarks(): void
    {
        if (! $this->exam_id || ! $this->class_id || ! $this->subject_id) {
            Notification::make()
                ->title('Please select Exam, Class, and Subject')
                ->warning()
                ->send();
            return;
        }

        // Find the matching schedule
        $schedule = ExamSchedule::where('exam_id', $this->exam_id)
            ->where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->where('subject_id', $this->subject_id)
            ->first();

        if (! $schedule) {
            Notification::make()
                ->title('No exam schedule found for this combination')
                ->danger()
                ->send();
            return;
        }

        $this->exam_schedule_id = $schedule->id;
        $this->full_marks = $schedule->full_marks;
        $this->pass_marks = $schedule->pass_marks;

        // Load students
        $students = Student::where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->enrolled()
            ->orderBy('roll_number')
            ->get();

        // Load existing marks
        $existingMarks = ExamMark::where('exam_schedule_id', $schedule->id)
            ->pluck('marks_obtained', 'student_id')
            ->toArray();

        $existingAbsent = ExamMark::where('exam_schedule_id', $schedule->id)
            ->pluck('is_absent', 'student_id')
            ->toArray();

        $existingRemarks = ExamMark::where('exam_schedule_id', $schedule->id)
            ->pluck('remarks', 'student_id')
            ->toArray();

        $this->marks = $students->map(fn (Student $s) => [
            'student_id'     => $s->id,
            'student_name'   => $s->full_name,
            'roll_number'    => $s->roll_number,
            'marks_obtained' => $existingMarks[$s->id] ?? '',
            'is_absent'      => $existingAbsent[$s->id] ?? false,
            'remarks'        => $existingRemarks[$s->id] ?? '',
        ])->toArray();
    }

    public function saveMarks(): void
    {
        if (! $this->exam_schedule_id || empty($this->marks)) {
            Notification::make()
                ->title('No marks data to save')
                ->warning()
                ->send();
            return;
        }

        // Validate marks
        foreach ($this->marks as $index => $entry) {
            if (! $entry['is_absent'] && $entry['marks_obtained'] !== '' && $entry['marks_obtained'] !== null) {
                $obtained = (float) $entry['marks_obtained'];
                if ($obtained < 0 || $obtained > $this->full_marks) {
                    Notification::make()
                        ->title("Invalid marks for {$entry['student_name']}: must be between 0 and {$this->full_marks}")
                        ->danger()
                        ->send();
                    return;
                }
            }
        }

        $marksData = collect($this->marks)->map(fn ($entry) => [
            'student_id'     => $entry['student_id'],
            'marks_obtained' => ($entry['is_absent'] || $entry['marks_obtained'] === '' || $entry['marks_obtained'] === null)
                ? null
                : (float) $entry['marks_obtained'],
            'is_absent'      => (bool) $entry['is_absent'],
            'remarks'        => $entry['remarks'] ?? null,
        ])->toArray();

        $service = app(ExamService::class);
        $count = $service->saveMarks($this->exam_schedule_id, $marksData, auth()->id());

        Notification::make()
            ->title("Marks saved for {$count} students")
            ->success()
            ->send();
    }

    public function calculateResults(): void
    {
        if (! $this->exam_id) {
            Notification::make()
                ->title('Please select an exam first')
                ->warning()
                ->send();
            return;
        }

        $service = app(ExamService::class);
        $count = $service->calculateResults($this->exam_id, $this->class_id);

        Notification::make()
            ->title("Results calculated for {$count} students")
            ->success()
            ->send();
    }
}
