<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Enums\AttendanceStatus;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\DailyActivityLog;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class MarkAttendance extends Page implements HasForms
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'mark_attendance_manual';

    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Mark Attendance';

    protected static string | \UnitEnum | null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.school-admin.pages.mark-attendance';

    // ── Filter Properties ────────────────────────────────────────
    public ?string $class_id = null;
    public ?string $section_id = null;
    public ?string $date = null;

    // ── Student Data ─────────────────────────────────────────────
    public array $students = [];
    public array $attendance = [];
    public bool $isLoaded = false;
    public bool $alreadyMarked = false;

    // ── Activity Score Data (PART 5e) ─────────────────────────────
    /** Keyed by student_id: ['participation_score' => 0..10, 'homework_score' => 0..10, 'behaviour_score' => 0..10] */
    public array $activityScores = [];
    public bool $showActivityScores = false;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    #[Computed]
    public function classes(): Collection
    {
        $query = SchoolClass::query()->orderBy('name');

        if ($this->actingAsTeacher()) {
            $ids = $this->teacherAllowedTuples()->pluck('class_id')->unique();
            $query->whereIn('id', $ids);
        }

        return $query->pluck('name', 'id');
    }

    #[Computed]
    public function sections(): Collection
    {
        if (! $this->class_id) {
            return collect();
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

        return $query->pluck('name', 'id');
    }

    /**
     * True when the page should restrict data to the logged-in teacher's
     * class_subjects rows.
     */
    protected function actingAsTeacher(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }

        $active = $user->active_role ?? $user->roles->first()?->name;

        return $active === 'TEACHER';
    }

    protected function teacherAllowedTuples(): \Illuminate\Support\Collection
    {
        return ClassSubject::query()
            ->where('teacher_id', auth()->guard('school_users')->id())
            ->get(['class_id', 'section_id', 'subject_id']);
    }

    public function updatedClassId(): void
    {
        $this->section_id = null;
        $this->isLoaded = false;
        $this->students = [];
    }

    public function loadStudents(): void
    {
        if (! $this->class_id || ! $this->section_id || ! $this->date) {
            Notification::make()
                ->title('Please select class, section and date')
                ->warning()
                ->send();
            return;
        }

        $service = app(AttendanceService::class);
        $date = Carbon::parse($this->date);

        $this->alreadyMarked = $service->isAlreadyMarked(
            $this->class_id,
            $this->section_id,
            $date
        );

        $classAttendance = $service->getClassAttendance(
            $this->class_id,
            $this->section_id,
            $date
        );

        $this->students = $classAttendance->map(fn ($s) => [
            'student_id'   => $s->student_id,
            'roll_number'  => $s->roll_number,
            'student_name' => $s->student_name,
        ])->toArray();

        $this->attendance = [];
        foreach ($classAttendance as $s) {
            $this->attendance[$s->student_id] = [
                'status'  => $s->status === 'not_marked' ? 'present' : $s->status,
                'remarks' => $s->remarks ?? '',
            ];
        }

        // ── Load existing activity scores for the date ────────────
        $this->activityScores = [];
        $academicYear = AcademicYear::where('is_current', true)->first();
        $existingLogs = DailyActivityLog::where('class_id', $this->class_id)
            ->whereDate('log_date', $this->date)
            ->get()
            ->keyBy('student_id');

        foreach ($this->students as $student) {
            $log = $existingLogs->get($student['student_id']);
            $this->activityScores[$student['student_id']] = [
                'participation_score' => $log?->participation_score ?? 0,
                'homework_score'      => $log?->homework_score ?? 0,
                'behaviour_score'     => $log?->behaviour_score ?? 0,
            ];
        }

        $this->isLoaded = true;

        if ($this->alreadyMarked) {
            Notification::make()
                ->title('Attendance already marked for this class on ' . $this->date)
                ->body('You can still edit individual records.')
                ->warning()
                ->send();
        }
    }

    public function markAllPresent(): void
    {
        foreach ($this->attendance as $studentId => $data) {
            $this->attendance[$studentId]['status'] = 'present';
        }
    }

    public function markAllAbsent(): void
    {
        foreach ($this->attendance as $studentId => $data) {
            $this->attendance[$studentId]['status'] = 'absent';
        }
    }

    public function saveAttendance(): void
    {
        if (empty($this->attendance)) {
            Notification::make()
                ->title('No attendance data to save')
                ->warning()
                ->send();
            return;
        }

        $service = app(AttendanceService::class);
        $academicYear = AcademicYear::where('is_current', true)->first();

        $records = [];
        foreach ($this->attendance as $studentId => $data) {
            $records[$studentId] = $data;
        }

        $result = $service->markClassAttendance(
            classId: $this->class_id,
            sectionId: $this->section_id,
            academicYearId: $academicYear?->id ?? '',
            date: Carbon::parse($this->date),
            records: $records,
            markedBy: auth()->guard('school_users')->id(),
        );

        Notification::make()
            ->title('Attendance Saved')
            ->body("Marked attendance for {$result['marked']} students.")
            ->success()
            ->send();
    }

    // ── PART 5e: Save daily activity scores ───────────────────────
    public function saveActivityScores(): void
    {
        if (empty($this->activityScores) || ! $this->class_id || ! $this->date) {
            Notification::make()
                ->title('No activity data to save')
                ->warning()
                ->send();
            return;
        }

        $academicYear = AcademicYear::where('is_current', true)->first();
        $saved = 0;

        foreach ($this->activityScores as $studentId => $scores) {
            $participation = min(10, max(0, (int) ($scores['participation_score'] ?? 0)));
            $homework      = min(10, max(0, (int) ($scores['homework_score'] ?? 0)));
            $behaviour     = min(10, max(0, (int) ($scores['behaviour_score'] ?? 0)));

            DailyActivityLog::updateOrCreate(
                [
                    'student_id'      => $studentId,
                    'class_id'        => $this->class_id,
                    'academic_year_id'=> $academicYear?->id ?? '',
                    'log_date'        => $this->date,
                ],
                [
                    'section_id'          => $this->section_id,
                    'recorded_by'         => auth()->guard('school_users')->id(),
                    'participation_score' => $participation,
                    'homework_score'      => $homework,
                    'behaviour_score'     => $behaviour,
                ]
            );

            $saved++;
        }

        Notification::make()
            ->title('Activity Scores Saved')
            ->body("Saved activity scores for {$saved} students.")
            ->success()
            ->send();
    }

    public function toggleActivityScores(): void
    {
        $this->showActivityScores = ! $this->showActivityScores;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
