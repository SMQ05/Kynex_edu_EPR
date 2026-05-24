<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Concerns\RendersReportPage;
use App\Models\Tenant\AnnualResult;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamMark;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Services\ExamService;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ExamReportsPage — printable exam report formats ported from InfixEdu.
 * One page with a "report type" selector to avoid seven near-identical
 * pages. All formats read existing exam data (no new tables) and reuse
 * ExamService for results/merit/report-card aggregation.
 *
 * Report types:
 *   tabulation       — full class grid: every subject × every student
 *   marksheet        — per-student subject breakdown + totals (report card)
 *   subject_wise     — one subject across the whole class
 *   merit_list       — ranked top performers
 *   progress_card    — per-student progress card (marks + grade + rank)
 *   previous_result  — finalised AnnualResult rows for a class/year
 *   exam_routine     — date-sheet (ExamSchedule) for an exam
 */
class ExamReportsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;
    use RendersReportPage;

    protected static string $rbacPermission = 'view_marks';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Exam Reports';

    protected static string | \UnitEnum | null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Exam Reports';

    protected string $view = 'filament.school-admin.pages.exam-reports';

    // ── Filters ─────────────────────────────────────────────────────
    public string $report_type = 'tabulation';
    public ?string $exam_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;
    public ?string $subject_id = null;
    public ?string $student_id = null;
    public ?string $academic_year_id = null;

    // ── Computed report payload (rebuilt on Generate) ───────────────
    public array $reportData = [];

    // ── Option lists ────────────────────────────────────────────────

    #[Computed]
    public function exams(): Collection
    {
        return Exam::orderByDesc('created_at')->pluck('name', 'id');
    }

    #[Computed]
    public function classes(): Collection
    {
        return SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function sections(): Collection
    {
        if (! $this->class_id) {
            return collect();
        }

        return Section::where('class_id', $this->class_id)->orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function subjects(): Collection
    {
        if (! $this->exam_id || ! $this->class_id) {
            return collect();
        }

        return ExamSchedule::where('exam_id', $this->exam_id)
            ->where('class_id', $this->class_id)
            ->with('subject')
            ->get()
            ->mapWithKeys(fn (ExamSchedule $s): array => [$s->subject_id => $s->subject?->name ?? '—'])
            ->filter(fn ($v, $k): bool => filled($k));
    }

    #[Computed]
    public function studentsForClass(): Collection
    {
        if (! $this->class_id) {
            return collect();
        }

        return Student::where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get()
            ->mapWithKeys(fn (Student $s): array => [
                $s->id => trim(($s->roll_number ? "{$s->roll_number} — " : '') . "{$s->first_name} {$s->last_name}"),
            ]);
    }

    #[Computed]
    public function academicYears(): Collection
    {
        return \App\Models\Tenant\AcademicYear::orderByDesc('start_date')->pluck('name', 'id');
    }

    // ── Reset cascading filters ─────────────────────────────────────

    public function updatedReportType(): void
    {
        $this->resetReport();
    }

    public function updatedClassId(): void
    {
        $this->section_id = null;
        $this->subject_id = null;
        $this->student_id = null;
        $this->resetReport();
    }

    public function updatedExamId(): void
    {
        $this->subject_id = null;
        $this->resetReport();
    }

    protected function resetReport(): void
    {
        $this->isLoaded = false;
        $this->reportData = [];
        $this->aiSummary = null;
    }

    // ── Generate ─────────────────────────────────────────────────────

    public function generateReport(): void
    {
        $this->aiSummary = null;
        $this->reportData = match ($this->report_type) {
            'tabulation'      => $this->buildTabulation(),
            'marksheet'       => $this->buildMarksheet(),
            'subject_wise'    => $this->buildSubjectWise(),
            'merit_list'      => $this->buildMeritList(),
            'progress_card'   => $this->buildProgressCard(),
            'previous_result' => $this->buildPreviousResult(),
            'exam_routine'    => $this->buildExamRoutine(),
            default           => [],
        };

        $this->isLoaded = true;
    }

    // ── Builders ─────────────────────────────────────────────────────

    /** Full grid: rows = students, columns = subjects, plus totals. */
    protected function buildTabulation(): array
    {
        if (! $this->exam_id || ! $this->class_id) {
            return [];
        }

        $schedules = ExamSchedule::where('exam_id', $this->exam_id)
            ->where('class_id', $this->class_id)
            ->with('subject')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $subjects = $schedules->map(fn (ExamSchedule $s): array => [
            'schedule_id' => $s->id,
            'name'        => $s->subject?->name ?? '—',
            'full_marks'  => (int) $s->full_marks,
            'pass_marks'  => (int) $s->pass_marks,
        ])->values()->all();

        $scheduleIds = $schedules->pluck('id');

        $students = Student::where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get();

        $marks = ExamMark::whereIn('exam_schedule_id', $scheduleIds)
            ->get()
            ->groupBy('student_id');

        $results = ExamResult::where('exam_id', $this->exam_id)
            ->where('class_id', $this->class_id)
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function (Student $student) use ($subjects, $marks, $results): array {
            $studentMarks = ($marks[$student->id] ?? collect())->keyBy('exam_schedule_id');
            $cells = [];
            $obtained = 0;
            $total = 0;

            foreach ($subjects as $subject) {
                $mark = $studentMarks[$subject['schedule_id']] ?? null;
                $value = $mark && ! $mark->is_absent ? (float) $mark->marks_obtained : null;
                $cells[] = [
                    'value'     => $value,
                    'is_absent' => (bool) ($mark?->is_absent),
                    'is_pass'   => $value !== null ? $value >= $subject['pass_marks'] : null,
                ];
                $obtained += $value ?? 0;
                $total += $subject['full_marks'];
            }

            $result = $results[$student->id] ?? null;

            return [
                'roll_number' => $student->roll_number,
                'name'        => $student->full_name,
                'cells'       => $cells,
                'obtained'    => $obtained,
                'total'       => $total,
                'percentage'  => $result?->percentage ?? ($total > 0 ? round($obtained / $total * 100, 2) : 0),
                'grade'       => $result?->grade,
                'rank'        => $result?->rank,
                'status'      => $result?->status?->label(),
            ];
        })->values()->all();

        return [
            'subjects' => $subjects,
            'rows'     => $rows,
            'heading'  => $this->contextHeading(),
        ];
    }

    /** Per-student subject breakdown — the classic report card. */
    protected function buildMarksheet(): array
    {
        if (! $this->exam_id || ! $this->student_id) {
            return [];
        }

        $card = app(ExamService::class)->getStudentReportCard($this->exam_id, $this->student_id);

        return [
            'card'    => $card,
            'heading' => $this->contextHeading(),
        ];
    }

    /** One subject across the whole class. */
    protected function buildSubjectWise(): array
    {
        if (! $this->exam_id || ! $this->class_id || ! $this->subject_id) {
            return [];
        }

        $schedule = ExamSchedule::where('exam_id', $this->exam_id)
            ->where('class_id', $this->class_id)
            ->where('subject_id', $this->subject_id)
            ->with('subject')
            ->first();

        if (! $schedule) {
            return [];
        }

        $students = Student::where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get();

        $marks = ExamMark::where('exam_schedule_id', $schedule->id)
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function (Student $student) use ($marks, $schedule): array {
            $mark = $marks[$student->id] ?? null;
            $value = $mark && ! $mark->is_absent ? (float) $mark->marks_obtained : null;

            return [
                'roll_number' => $student->roll_number,
                'name'        => $student->full_name,
                'is_absent'   => (bool) ($mark?->is_absent),
                'marks'       => $value,
                'is_pass'     => $value !== null ? $value >= (int) $schedule->pass_marks : null,
                'remarks'     => $mark?->remarks,
            ];
        })->values()->all();

        $scored = collect($rows)->filter(fn ($r): bool => $r['marks'] !== null);

        return [
            'subject'    => $schedule->subject?->name ?? '—',
            'full_marks' => (int) $schedule->full_marks,
            'pass_marks' => (int) $schedule->pass_marks,
            'rows'       => $rows,
            'stats'      => [
                'appeared' => $scored->count(),
                'passed'   => $scored->where('is_pass', true)->count(),
                'highest'  => $scored->max('marks') ?? 0,
                'lowest'   => $scored->min('marks') ?? 0,
                'average'  => $scored->isNotEmpty() ? round($scored->avg('marks'), 2) : 0,
            ],
            'heading'    => $this->contextHeading(),
        ];
    }

    /** Ranked top performers (reuses ExamService::getMeritList). */
    protected function buildMeritList(): array
    {
        if (! $this->exam_id || ! $this->class_id) {
            return [];
        }

        $list = app(ExamService::class)->getMeritList($this->exam_id, $this->class_id, 50);

        return [
            'rows'    => $list->map(fn ($r): array => [
                'position'    => (int) $r->position,
                'name'        => trim("{$r->first_name} {$r->last_name}"),
                'admission'   => $r->admission_number,
                'obtained'    => (float) $r->marks_obtained,
                'total'       => (float) $r->total_marks,
                'percentage'  => (float) $r->percentage,
                'grade'       => $r->grade,
            ])->all(),
            'heading' => $this->contextHeading(),
        ];
    }

    /** Per-student progress card (marks + grade + rank summary). */
    protected function buildProgressCard(): array
    {
        if (! $this->exam_id || ! $this->student_id) {
            return [];
        }

        $card = app(ExamService::class)->getStudentReportCard($this->exam_id, $this->student_id);
        $summary = app(ExamService::class)->getClassResultSummary(
            $this->exam_id,
            $card['student']->class_id ?? $this->class_id,
        );

        return [
            'card'         => $card,
            'classSummary' => $summary,
            'heading'      => $this->contextHeading(),
        ];
    }

    /** Finalised AnnualResult rows for a class + academic year. */
    protected function buildPreviousResult(): array
    {
        if (! $this->class_id) {
            return [];
        }

        $rows = AnnualResult::with(['student', 'academicYear'])
            ->where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->when($this->academic_year_id, fn ($q) => $q->where('academic_year_id', $this->academic_year_id))
            ->orderBy('rank')
            ->get();

        return [
            'rows'    => $rows->map(fn (AnnualResult $r): array => [
                'rank'       => $r->rank,
                'name'       => $r->student?->full_name ?? '—',
                'admission'  => $r->student?->admission_number,
                'year'       => $r->academicYear?->name,
                'percentage' => $r->final_percentage,
                'grade'      => $r->grade,
                'status'     => $r->status,
            ])->all(),
            'heading' => $this->contextHeading(),
        ];
    }

    /** Date-sheet / exam routine (ExamSchedule rows for an exam). */
    protected function buildExamRoutine(): array
    {
        if (! $this->exam_id) {
            return [];
        }

        $rows = ExamSchedule::where('exam_id', $this->exam_id)
            ->when($this->class_id, fn ($q) => $q->where('class_id', $this->class_id))
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->with(['subject', 'schoolClass', 'section'])
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        return [
            'rows'    => $rows->map(fn (ExamSchedule $s): array => [
                'date'       => $s->exam_date?->format('d M Y, l'),
                'start'      => $s->start_time?->format('H:i'),
                'end'        => $s->end_time?->format('H:i'),
                'class'      => $s->schoolClass?->name,
                'section'    => $s->section?->name,
                'subject'    => $s->subject?->name ?? '—',
                'room'       => $s->room,
                'full_marks' => (int) $s->full_marks,
            ])->all(),
            'heading' => $this->contextHeading(),
        ];
    }

    // ── Context label for headings / PDF ────────────────────────────

    protected function contextHeading(): string
    {
        $parts = [];
        if ($this->exam_id) {
            $parts[] = (string) ($this->exams[$this->exam_id] ?? '');
        }
        if ($this->class_id) {
            $parts[] = 'Class ' . (string) ($this->classes[$this->class_id] ?? '');
        }
        if ($this->section_id) {
            $parts[] = 'Section ' . (string) ($this->sections[$this->section_id] ?? '');
        }

        return implode(' · ', array_filter($parts));
    }

    public function hasData(): bool
    {
        if (empty($this->reportData)) {
            return false;
        }

        return match ($this->report_type) {
            'marksheet', 'progress_card' => ! empty($this->reportData['card']),
            'tabulation', 'subject_wise', 'merit_list', 'previous_result', 'exam_routine'
                => ! empty($this->reportData['rows']) || ! empty($this->reportData['subjects']),
            default => false,
        };
    }

    // ── Header actions: PDF + AI summary ────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => $this->isLoaded && $this->hasData())
                ->action(fn (): StreamedResponse => $this->downloadPdf()),

            Action::make('aiSummary')
                ->label('AI Summary')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn (): bool => $this->isLoaded && $this->hasData() && \App\Services\Ai\AiAvailability::enabled())
                ->action(fn (): null => $this->generateAiSummary()),
        ];
    }

    public function downloadPdf(): StreamedResponse
    {
        $title = ucwords(str_replace('_', ' ', $this->report_type));

        return $this->streamPdf(
            view: 'pdf.exam-report',
            data: [
                'reportType' => $this->report_type,
                'title'      => $title,
                'data'       => $this->reportData,
            ],
            filename: "ExamReport_{$this->report_type}_" . now()->format('Ymd_His') . '.pdf',
            orientation: in_array($this->report_type, ['tabulation', 'exam_routine'], true) ? 'landscape' : 'portrait',
        );
    }

    public function generateAiSummary(): null
    {
        $title = ucwords(str_replace('_', ' ', $this->report_type));

        $this->runAiSummary(
            data:        $this->reportData,
            instruction: "Write a concise executive summary of this {$title} for {$this->contextHeading()}. "
                . 'Highlight overall performance, pass/fail patterns, top and bottom performers, '
                . 'and any subjects or students needing attention. Cite the actual numbers.',
            feature:     'exam_report_summary',
        );

        return null;
    }
}
