<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Enums\ExamResultStatus;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\SchoolClass;
use App\Services\ExamService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ResultsPage extends Page implements HasForms
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_marks';

    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trophy';

    protected static string | \UnitEnum | null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Exam Results';

    protected static ?string $title = 'Exam Results & Report Cards';

    protected string $view = 'filament.school-admin.pages.results-page';

    // ── Filters ────────────────────────────────────────────────────

    public ?string $exam_id = null;
    public ?string $class_id = null;
    public string $view_mode = 'class_results'; // class_results | merit_list | report_card

    public ?string $student_id_for_card = null;

    // ── Computed Data ──────────────────────────────────────────────

    #[Computed]
    public function exams(): Collection
    {
        return Exam::orderByDesc('created_at')->get();
    }

    #[Computed]
    public function classes(): Collection
    {
        return SchoolClass::orderBy('name')->get();
    }

    #[Computed]
    public function results(): Collection
    {
        if (! $this->exam_id || ! $this->class_id) {
            return collect();
        }

        return ExamResult::with('student')
            ->where('exam_id', $this->exam_id)
            ->where('class_id', $this->class_id)
            ->orderBy('rank')
            ->get();
    }

    #[Computed]
    public function classSummary(): array
    {
        if (! $this->exam_id || ! $this->class_id) {
            return [];
        }

        return app(ExamService::class)->getClassResultSummary($this->exam_id, $this->class_id);
    }

    #[Computed]
    public function meritList(): Collection
    {
        if (! $this->exam_id || ! $this->class_id) {
            return collect();
        }

        return app(ExamService::class)->getMeritList($this->exam_id, $this->class_id, 20);
    }

    #[Computed]
    public function reportCard(): array
    {
        if (! $this->exam_id || ! $this->student_id_for_card) {
            return [];
        }

        return app(ExamService::class)->getStudentReportCard($this->exam_id, $this->student_id_for_card);
    }

    public function viewReportCard(string $studentId): void
    {
        $this->student_id_for_card = $studentId;
        $this->view_mode = 'report_card';
    }

    public function backToResults(): void
    {
        $this->student_id_for_card = null;
        $this->view_mode = 'class_results';
    }
}
