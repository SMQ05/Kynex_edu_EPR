<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Concerns\RendersReportPage;
use App\Models\Tenant\BehaviorIncident;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Behaviour Report (Infix "Behaviour → Reports"). Read-only summary of
 * incidents per student / class over a date range, with net merit/demerit
 * points and an optional 🤖 AI narrative + suggested interventions. Reads the
 * EXISTING `behavior_incidents` table; does not modify BehaviorIncidentResource.
 */
class BehaviorReportPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;
    use RendersReportPage;

    protected static string $rbacPermission = 'manage_behavior_records';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Health & Wellbeing';

    protected static ?string $navigationLabel = 'Behaviour Report';

    protected static ?int $navigationSort = 22;

    protected static ?string $title = 'Behaviour Report';

    protected string $view = 'filament.school-admin.pages.behavior-report';

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
                $s->id => trim(($s->roll_number ? "{$s->roll_number} — " : '') . $s->full_name),
            ]);
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

    public function generateReport(): void
    {
        $this->aiSummary = null;
        $this->reportData = $this->buildReport();
        $this->isLoaded = true;
    }

    /** @return array<string,mixed> */
    protected function buildReport(): array
    {
        if (! $this->class_id) {
            return [];
        }

        $from = $this->date_from ? Carbon::parse($this->date_from)->toDateString() : null;
        $to = $this->date_to ? Carbon::parse($this->date_to)->toDateString() : null;

        $incidents = BehaviorIncident::query()
            ->where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->when($this->student_id, fn ($q) => $q->where('student_id', $this->student_id))
            ->when($from && $to, fn ($q) => $q->whereBetween('incident_date', [$from, $to]))
            ->with('student')
            ->orderByDesc('incident_date')
            ->get();

        if ($incidents->isEmpty()) {
            return [];
        }

        // Per-student rollup of counts + net points.
        $perStudent = $incidents
            ->groupBy('student_id')
            ->map(function (Collection $group): array {
                $first = $group->first();
                $positive = $group->where('incident_type', \App\Enums\BehaviorIncidentType::Positive)->count();
                $negative = $group->where('incident_type', \App\Enums\BehaviorIncidentType::Negative)->count();
                $netPoints = (int) $group->sum(fn (BehaviorIncident $i): int => (int) ($i->points ?? 0));

                return [
                    'student'    => $first->student?->full_name ?? '—',
                    'roll'       => $first->student?->roll_number,
                    'total'      => $group->count(),
                    'positive'   => $positive,
                    'negative'   => $negative,
                    'net_points' => $netPoints,
                ];
            })
            ->sortBy('roll')
            ->values()
            ->all();

        return [
            'rows'  => $perStudent,
            'stats' => [
                'total'     => $incidents->count(),
                'positive'  => $incidents->where('incident_type', \App\Enums\BehaviorIncidentType::Positive)->count(),
                'negative'  => $incidents->where('incident_type', \App\Enums\BehaviorIncidentType::Negative)->count(),
                'students'  => count($perStudent),
            ],
            'heading' => $this->contextHeading(),
        ];
    }

    protected function contextHeading(): string
    {
        $parts = [];
        if ($this->class_id) {
            $parts[] = 'Class ' . (string) ($this->classes[$this->class_id] ?? '');
        }
        if ($this->section_id) {
            $parts[] = 'Section ' . (string) ($this->sections[$this->section_id] ?? '');
        }
        if ($this->date_from && $this->date_to) {
            $parts[] = "{$this->date_from} → {$this->date_to}";
        }

        return implode(' · ', array_filter($parts));
    }

    public function hasData(): bool
    {
        return ! empty($this->reportData['rows']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiSummary')
                ->label('AI Summary & Interventions')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn (): bool => $this->isLoaded && $this->hasData() && \App\Services\Ai\AiAvailability::enabled())
                ->action(fn (): null => $this->generateAiSummary()),
        ];
    }

    public function generateAiSummary(): null
    {
        $this->runAiSummary(
            data:        $this->reportData,
            instruction: "Summarise the behaviour picture for {$this->contextHeading()}. Highlight students who need "
                . 'positive reinforcement vs. those needing support, surface patterns, and suggest 2-3 concrete, '
                . 'student-welfare-focused interventions. Cite the actual counts/points.',
            feature:     'behavior_report_summary',
        );

        return null;
    }
}
