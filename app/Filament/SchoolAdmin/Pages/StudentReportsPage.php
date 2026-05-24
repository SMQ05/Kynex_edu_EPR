<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Concerns\RendersReportPage;
use App\Models\Tenant\HomeworkSubmission;
use App\Models\Tenant\HostelAllocation;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentGuardian;
use App\Models\Tenant\SubjectAttendanceRecord;
use App\Models\Tenant\TransportAssignment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * StudentReportsPage — student-centric report formats ported from InfixEdu.
 * Single page + report-type selector (no new tables; reads existing data).
 *
 * Report types:
 *   subject_attendance — per-subject attendance summary for a class/section
 *   homework_eval      — homework submission/grading report for a class
 *   student_history    — one student's full profile snapshot
 *   transport          — transport assignments for a class/section
 *   dormitory          — hostel allocations for a class/section
 *   guardian           — guardian contact directory for a class/section
 */
class StudentReportsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;
    use RendersReportPage;

    protected static string $rbacPermission = 'view_students';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Student Reports';

    protected static string | \UnitEnum | null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Student Reports';

    protected string $view = 'filament.school-admin.pages.student-reports';

    // ── Filters ─────────────────────────────────────────────────────
    public string $report_type = 'subject_attendance';
    public ?string $class_id = null;
    public ?string $section_id = null;
    public ?string $student_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;

    public array $reportData = [];

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
    }

    // ── Option lists ────────────────────────────────────────────────

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
    public function studentsForClass(): Collection
    {
        if (! $this->class_id) {
            return collect();
        }

        return Student::where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->orderBy('roll_number')
            ->get()
            ->mapWithKeys(fn (Student $s): array => [
                $s->id => trim(($s->roll_number ? "{$s->roll_number} — " : '') . "{$s->first_name} {$s->last_name}"),
            ]);
    }

    // ── Cascading resets ─────────────────────────────────────────────

    public function updatedReportType(): void
    {
        $this->resetReport();
    }

    public function updatedClassId(): void
    {
        $this->section_id = null;
        $this->student_id = null;
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
            'subject_attendance' => $this->buildSubjectAttendance(),
            'homework_eval'      => $this->buildHomeworkEval(),
            'student_history'    => $this->buildStudentHistory(),
            'transport'          => $this->buildTransport(),
            'dormitory'          => $this->buildDormitory(),
            'guardian'           => $this->buildGuardian(),
            default              => [],
        };

        $this->isLoaded = true;
    }

    // ── Builders ─────────────────────────────────────────────────────

    /** Per-subject attendance counts for each student in the class/section. */
    protected function buildSubjectAttendance(): array
    {
        if (! $this->class_id || ! $this->date_from || ! $this->date_to) {
            return [];
        }

        $from = Carbon::parse($this->date_from)->toDateString();
        $to = Carbon::parse($this->date_to)->toDateString();

        $records = SubjectAttendanceRecord::query()
            ->where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->when($this->student_id, fn ($q) => $q->where('student_id', $this->student_id))
            ->whereBetween('date', [$from, $to])
            ->with(['student', 'subject'])
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        $rows = $records
            ->groupBy(fn (SubjectAttendanceRecord $r): string => $r->student_id . '|' . $r->subject_id)
            ->map(function (Collection $group): array {
                $first = $group->first();
                $total = $group->count();
                $present = $group->whereIn('status', [
                    \App\Enums\AttendanceStatus::Present,
                    \App\Enums\AttendanceStatus::Late,
                    \App\Enums\AttendanceStatus::HalfDay,
                ])->count();

                return [
                    'student'    => $first->student?->full_name ?? '—',
                    'roll'       => $first->student?->roll_number,
                    'subject'    => $first->subject?->name ?? '—',
                    'total'      => $total,
                    'present'    => $present,
                    'absent'     => $group->where('status', \App\Enums\AttendanceStatus::Absent)->count(),
                    'late'       => $group->where('status', \App\Enums\AttendanceStatus::Late)->count(),
                    'percentage' => $total > 0 ? round($present / $total * 100, 1) : 0,
                ];
            })
            ->sortBy([['roll', 'asc'], ['subject', 'asc']])
            ->values()
            ->all();

        return ['rows' => $rows, 'heading' => $this->contextHeading()];
    }

    /** Homework submissions + grading for a class/section. */
    protected function buildHomeworkEval(): array
    {
        if (! $this->class_id) {
            return [];
        }

        $from = $this->date_from ? Carbon::parse($this->date_from)->startOfDay() : null;
        $to = $this->date_to ? Carbon::parse($this->date_to)->endOfDay() : null;

        $submissions = HomeworkSubmission::query()
            ->whereHas('homework', function ($q): void {
                $q->where('class_id', $this->class_id)
                    ->when($this->section_id, fn ($qq) => $qq->where('section_id', $this->section_id));
            })
            ->when($this->student_id, fn ($q) => $q->where('student_id', $this->student_id))
            ->when($from && $to, fn ($q) => $q->whereBetween('submitted_at', [$from, $to]))
            ->with(['student', 'homework.subject'])
            ->orderByDesc('submitted_at')
            ->get();

        if ($submissions->isEmpty()) {
            return [];
        }

        $rows = $submissions->map(fn (HomeworkSubmission $s): array => [
            'student'      => $s->student?->full_name ?? '—',
            'roll'         => $s->student?->roll_number,
            'homework'     => $s->homework?->title ?? '—',
            'subject'      => $s->homework?->subject?->name ?? '—',
            'submitted_at' => $s->submitted_at?->format('d M Y'),
            'grade'        => $s->grade,
            'marks'        => $s->marks_obtained,
            'total_marks'  => $s->total_marks,
            'percentage'   => $s->percentage(),
            'graded'       => $s->isGraded(),
        ])->all();

        $graded = collect($rows)->where('graded', true);

        return [
            'rows'  => $rows,
            'stats' => [
                'total'   => count($rows),
                'graded'  => $graded->count(),
                'pending' => count($rows) - $graded->count(),
                'avg_pct' => $graded->whereNotNull('percentage')->isNotEmpty()
                    ? round($graded->whereNotNull('percentage')->avg('percentage'), 1)
                    : null,
            ],
            'heading' => $this->contextHeading(),
        ];
    }

    /** Full profile snapshot for a single student. */
    protected function buildStudentHistory(): array
    {
        if (! $this->student_id) {
            return [];
        }

        $student = Student::with([
            'schoolClass', 'section', 'category', 'academicYear',
            'guardians',
            'promotions.fromClass', 'promotions.toClass', 'promotions.toAcademicYear',
        ])->find($this->student_id);

        if (! $student) {
            return [];
        }

        return [
            'student'    => $student,
            'guardians'  => $student->guardians,
            'promotions' => $student->promotions->sortByDesc('created_at')->values(),
            'heading'    => $student->full_name,
        ];
    }

    /** Transport assignments for a class/section. */
    protected function buildTransport(): array
    {
        if (! $this->class_id) {
            return [];
        }

        $rows = TransportAssignment::query()
            ->whereHas('student', function ($q): void {
                $q->where('class_id', $this->class_id)
                    ->when($this->section_id, fn ($qq) => $qq->where('section_id', $this->section_id));
            })
            ->when($this->student_id, fn ($q) => $q->where('student_id', $this->student_id))
            ->with(['student', 'route', 'stop'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'rows'    => $rows->map(fn (TransportAssignment $a): array => [
                'student'   => $a->student?->full_name ?? '—',
                'roll'      => $a->student?->roll_number,
                'route'     => $a->route?->name,
                'stop'      => $a->stop?->name,
                'direction' => $a->direction,
                'active'    => (bool) $a->is_active,
            ])->all(),
            'heading' => $this->contextHeading(),
        ];
    }

    /** Hostel/dormitory allocations for a class/section. */
    protected function buildDormitory(): array
    {
        if (! $this->class_id) {
            return [];
        }

        $rows = HostelAllocation::query()
            ->whereHas('student', function ($q): void {
                $q->where('class_id', $this->class_id)
                    ->when($this->section_id, fn ($qq) => $qq->where('section_id', $this->section_id));
            })
            ->when($this->student_id, fn ($q) => $q->where('student_id', $this->student_id))
            ->with(['student', 'room.roomType', 'room.building'])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'rows'    => $rows->map(fn (HostelAllocation $a): array => [
                'student'  => $a->student?->full_name ?? '—',
                'roll'     => $a->student?->roll_number,
                'building' => $a->room?->building?->name,
                'room'     => $a->room?->room_number ?? $a->room?->name,
                'bed'      => $a->bed_number,
                'status'   => $a->status,
                'check_in' => $a->check_in_date?->format('d M Y'),
            ])->all(),
            'heading' => $this->contextHeading(),
        ];
    }

    /** Guardian contact directory for a class/section. */
    protected function buildGuardian(): array
    {
        if (! $this->class_id) {
            return [];
        }

        $rows = StudentGuardian::query()
            ->whereHas('student', function ($q): void {
                $q->where('class_id', $this->class_id)
                    ->when($this->section_id, fn ($qq) => $qq->where('section_id', $this->section_id));
            })
            ->when($this->student_id, fn ($q) => $q->where('student_id', $this->student_id))
            ->with('student')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [
            'rows'    => $rows->map(fn (StudentGuardian $g): array => [
                'student'      => $g->student?->full_name ?? '—',
                'roll'         => $g->student?->roll_number,
                'guardian'     => $g->name,
                'relationship' => $g->relationship,
                'phone'        => $g->phone,
                'email'        => $g->email,
                'is_primary'   => (bool) $g->is_primary_contact,
            ])->all(),
            'heading' => $this->contextHeading(),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────

    protected function contextHeading(): string
    {
        $parts = [];
        if ($this->class_id) {
            $parts[] = 'Class ' . (string) ($this->classes[$this->class_id] ?? '');
        }
        if ($this->section_id) {
            $parts[] = 'Section ' . (string) ($this->sections[$this->section_id] ?? '');
        }
        if ($this->date_from && $this->date_to && in_array($this->report_type, ['subject_attendance', 'homework_eval'], true)) {
            $parts[] = "{$this->date_from} → {$this->date_to}";
        }

        return implode(' · ', array_filter($parts));
    }

    public function hasData(): bool
    {
        if (empty($this->reportData)) {
            return false;
        }

        if ($this->report_type === 'student_history') {
            return ! empty($this->reportData['student']);
        }

        return ! empty($this->reportData['rows']);
    }

    // ── Header actions ───────────────────────────────────────────────

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
            view: 'pdf.student-report',
            data: [
                'reportType' => $this->report_type,
                'title'      => $title,
                'data'       => $this->reportData,
            ],
            filename: "StudentReport_{$this->report_type}_" . now()->format('Ymd_His') . '.pdf',
        );
    }

    public function generateAiSummary(): null
    {
        $title = ucwords(str_replace('_', ' ', $this->report_type));

        $this->runAiSummary(
            data:        $this->reportData,
            instruction: "Write a concise executive summary of this {$title} for {$this->contextHeading()}. "
                . 'Surface patterns, outliers and anything that needs follow-up. Cite the actual numbers.',
            feature:     'student_report_summary',
        );

        return null;
    }
}
