<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\AcademicYearResource;
use App\Filament\SchoolAdmin\Resources\ExamResource;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Exam;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Single-screen overview of how the annual result is composed:
 *  - Academic-year split between exams / homework / class assignments
 *  - Per-exam weightage within the exam pool
 *
 * Read-only summary; clicking a row jumps to the editable resource.
 */
class GradingWeights extends Page
{
    use HasPermissionCheck;

    // Read-only overview of how the annual result is composed. Anyone
    // with view_marks (teachers, admins, exam staff) may open it; editing
    // happens on the AcademicYear and Exam resources, which retain their
    // own write-permission gates.
    protected static string $rbacPermission = 'view_marks';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static string | \UnitEnum | null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Grading Weights';

    protected static ?string $title = 'Grading Weights Overview';

    protected string $view = 'filament.school-admin.pages.grading-weights';

    public ?string $academic_year_id = null;

    // Always show in nav and allow authenticated users to land on the page;
    // users without view_marks see an in-layout "Access Restricted" message
    // (see getView() below) instead of a stripped 403.
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->guard('school_users')->check();
    }

    public static function canAccess(): bool
    {
        return auth()->guard('school_users')->check();
    }

    public function getView(): string
    {
        if (! $this->userCanViewMarks()) {
            return 'filament.school-admin.pages.forbidden';
        }
        return $this->view;
    }

    private function userCanViewMarks(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD', 'SCHOOL_ADMIN'])) {
            return true;
        }
        try {
            return $user->hasPermissionTo('view_marks');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }

    public function mount(): void
    {
        if (! $this->userCanViewMarks()) {
            return;
        }
        $this->academic_year_id = AcademicYear::query()
            ->where('is_current', true)
            ->value('id')
            ?? AcademicYear::query()->orderByDesc('start_date')->value('id');
    }

    #[Computed]
    public function academicYearOptions(): array
    {
        return AcademicYear::query()
            ->orderByDesc('start_date')
            ->pluck('name', 'id')
            ->all();
    }

    #[Computed]
    public function academicYear(): ?AcademicYear
    {
        if (! $this->academic_year_id) {
            return null;
        }
        return AcademicYear::find($this->academic_year_id);
    }

    #[Computed]
    public function exams(): Collection
    {
        if (! $this->academic_year_id) {
            return collect();
        }

        return Exam::query()
            ->where('academic_year_id', $this->academic_year_id)
            ->orderBy('start_date')
            ->orderBy('created_at')
            ->get();
    }

    public function yearWeightTotal(): int
    {
        if (! $this->academicYear) {
            return 0;
        }
        return (int) $this->academicYear->exam_weight_percent
            + (int) $this->academicYear->homework_weight_percent
            + (int) $this->academicYear->class_assignment_weight_percent;
    }

    public function examWeightTotal(): int
    {
        return (int) $this->exams
            ->filter(fn (Exam $e) => (bool) ($e->include_in_annual_result ?? true))
            ->sum('weightage_percent');
    }

    public function editYearUrl(): ?string
    {
        return $this->academic_year_id
            ? AcademicYearResource::getUrl('edit', ['record' => $this->academic_year_id])
            : null;
    }

    public function editExamUrl(string $examId): string
    {
        return ExamResource::getUrl('edit', ['record' => $examId]);
    }
}
