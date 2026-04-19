<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Enums\AttendanceStatus;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class AttendanceReport extends Page implements HasForms
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_attendance_reports';

    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Attendance Reports';

    protected static string | \UnitEnum | null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.school-admin.pages.attendance-report';

    // ── Filter Properties ────────────────────────────────────────
    public ?string $class_id = null;
    public ?string $section_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $student_id = null;
    public string $report_type = 'class_summary'; // class_summary | student_detail | daily

    // ── Report Data ─────────────────────────────────────────────
    public array $reportData = [];
    public bool $isLoaded = false;

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
    }

    #[Computed]
    public function sections(): Collection
    {
        if (! $this->class_id) {
            return collect();
        }

        return Section::where('class_id', $this->class_id)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    #[Computed]
    public function students(): Collection
    {
        if (! $this->class_id || ! $this->section_id) {
            return collect();
        }

        return Student::where('class_id', $this->class_id)
            ->where('section_id', $this->section_id)
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get()
            ->mapWithKeys(fn (Student $s) => [$s->id => "{$s->roll_number} — {$s->first_name} {$s->last_name}"]);
    }

    public function updatedClassId(): void
    {
        $this->section_id = null;
        $this->student_id = null;
        $this->isLoaded = false;
        $this->reportData = [];
    }

    public function generateReport(): void
    {
        if (! $this->class_id || ! $this->section_id || ! $this->date_from || ! $this->date_to) {
            return;
        }

        $from = Carbon::parse($this->date_from);
        $to = Carbon::parse($this->date_to);
        $service = app(AttendanceService::class);

        match ($this->report_type) {
            'class_summary'  => $this->generateClassSummary($from, $to, $service),
            'student_detail' => $this->generateStudentDetail($from, $to, $service),
            'daily'          => $this->generateDailyReport($from, $to),
        };

        $this->isLoaded = true;
    }

    protected function generateClassSummary(Carbon $from, Carbon $to, AttendanceService $service): void
    {
        $students = Student::where('class_id', $this->class_id)
            ->where('section_id', $this->section_id)
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get();

        $this->reportData = $students->map(function (Student $student) use ($from, $to, $service) {
            $summary = $service->getStudentAttendanceSummary($student->id, $from, $to);

            return [
                'student_id'   => $student->id,
                'roll_number'  => $student->roll_number,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                ...$summary,
            ];
        })->toArray();
    }

    protected function generateStudentDetail(Carbon $from, Carbon $to, AttendanceService $service): void
    {
        if (! $this->student_id) {
            $this->reportData = [];
            return;
        }

        $student = Student::find($this->student_id);
        if (! $student) {
            $this->reportData = [];
            return;
        }

        $records = AttendanceRecord::where('student_id', $this->student_id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get();

        $summary = $service->getStudentAttendanceSummary($this->student_id, $from, $to);

        $this->reportData = [
            'student' => [
                'name'        => $student->first_name . ' ' . $student->last_name,
                'roll_number' => $student->roll_number,
            ],
            'summary' => $summary,
            'records' => $records->map(fn ($r) => [
                'date'         => $r->date->format('Y-m-d'),
                'day'          => $r->date->format('l'),
                'status'       => $r->status->value,
                'status_label' => $r->status->label(),
                'status_color' => $r->status->color(),
                'remarks'      => $r->remarks,
                'late_minutes' => $r->late_minutes,
            ])->toArray(),
        ];
    }

    protected function generateDailyReport(Carbon $from, Carbon $to): void
    {
        $records = AttendanceRecord::where('class_id', $this->class_id)
            ->where('section_id', $this->section_id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("date, status, COUNT(*) as count")
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        $grouped = $records->groupBy(fn ($r) => $r->date->format('Y-m-d'));

        $this->reportData = $grouped->map(function ($dateRecords, $date) {
            $statusCounts = [];
            foreach (AttendanceStatus::cases() as $status) {
                $statusCounts[$status->value] = $dateRecords->where('status', $status->value)->sum('count');
            }

            return [
                'date'    => $date,
                'day'     => Carbon::parse($date)->format('l'),
                'total'   => $dateRecords->sum('count'),
                'statuses' => $statusCounts,
            ];
        })->values()->toArray();
    }
}
