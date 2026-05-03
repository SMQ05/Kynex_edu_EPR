<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\ClassRoutineResource\Pages;

use App\Enums\DayOfWeek;
use App\Filament\SchoolAdmin\Pages\ClassRoutinePlanner;
use App\Filament\SchoolAdmin\Resources\ClassRoutineResource;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\ClassRoutine;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ListClassRoutines extends ListRecords
{
    protected static string $resource = ClassRoutineResource::class;

    #[Url(as: 'year')]
    public ?string $selectedAcademicYearId = null;

    #[Url(as: 'class')]
    public ?string $selectedClassId = null;

    #[Url(as: 'section')]
    public ?string $selectedSectionId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('planner')
                ->label('Routine Planner')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(ClassRoutinePlanner::getUrl()),
            Actions\Action::make('print')
                ->label('Print Timetable')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url('#')
                ->extraAttributes([
                    'onclick' => 'window.print(); return false;',
                ]),
            Actions\CreateAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.school-admin.resources.class-routine-resource.pages.timetable-index')
                    ->viewData(fn ($livewire): array => ['page' => $livewire]),
            ]);
    }

    public function updatedSelectedClassId(): void
    {
        $this->selectedSectionId = null;
    }

    public function academicYearOptions(): array
    {
        return AcademicYear::query()
            ->orderByDesc('is_current')
            ->orderByDesc('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function classOptions(): array
    {
        return SchoolClass::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function sectionOptions(): array
    {
        return Section::query()
            ->when(
                $this->selectedClassId,
                fn ($query) => $query->where('class_id', $this->selectedClassId),
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function days(): array
    {
        return DayOfWeek::cases();
    }

    public function timetableGroups(): Collection
    {
        return ClassRoutine::query()
            ->with(['academicYear', 'schoolClass.campus', 'section', 'subject', 'teacher'])
            ->when(
                $this->selectedAcademicYearId,
                fn ($query) => $query->where('academic_year_id', $this->selectedAcademicYearId),
            )
            ->when(
                $this->selectedClassId,
                fn ($query) => $query->where('class_id', $this->selectedClassId),
            )
            ->when(
                $this->selectedSectionId,
                fn ($query) => $query->where('section_id', $this->selectedSectionId),
            )
            ->orderBy('academic_year_id')
            ->orderBy('class_id')
            ->orderBy('section_id')
            ->orderBy('period_number')
            ->get()
            ->groupBy(fn (ClassRoutine $routine): string => implode('|', [
                $routine->academic_year_id,
                $routine->class_id,
                $routine->section_id ?: 'none',
            ]))
            ->map(fn (Collection $routines): array => $this->makeTimetableGroup($routines))
            ->sortBy(fn (array $group): string => implode('|', [
                $group['academic_year_name'],
                $group['class_name'],
                $group['section_name'],
            ]))
            ->values();
    }

    public function formatRoutineTime(?object $time): string
    {
        return $time ? $time->format('H:i') : '';
    }

    public function editRoutineUrl(ClassRoutine $routine): string
    {
        return ClassRoutineResource::getUrl('edit', ['record' => $routine]);
    }

    private function makeTimetableGroup(Collection $routines): array
    {
        $first = $routines->first();

        $slots = $routines
            ->groupBy('period_number')
            ->map(function (Collection $periodRows, int|string $periodNumber): array {
                $canonical = $periodRows->first(
                    fn (ClassRoutine $routine): bool => $routine->day_of_week === DayOfWeek::Monday,
                ) ?? $periodRows->first();

                $breakRow = $periodRows->first(fn (ClassRoutine $routine): bool => $routine->is_break);
                $isBreak = (bool) $breakRow;
                $displayRow = $breakRow ?? $canonical;

                return [
                    'period_number' => (int) $periodNumber,
                    'is_break' => $isBreak,
                    'label' => $isBreak
                        ? ($displayRow->break_label ?: 'Break')
                        : 'Period ' . $periodNumber,
                    'start_time' => $this->formatRoutineTime($displayRow->start_time),
                    'end_time' => $this->formatRoutineTime($displayRow->end_time),
                ];
            })
            ->sortBy('period_number')
            ->values()
            ->all();

        $cells = [];

        foreach ($routines as $routine) {
            $day = $routine->day_of_week instanceof DayOfWeek
                ? $routine->day_of_week->value
                : (string) $routine->day_of_week;

            $cells[$day][(int) $routine->period_number] = $routine;
        }

        return [
            'academic_year_name' => $first?->academicYear?->name ?? 'Academic Year',
            'class_name' => $first?->schoolClass?->name ?? 'Class',
            'section_name' => $first?->section?->name ?? 'All Sections',
            'campus_name' => $first?->schoolClass?->campus?->name,
            'slots' => $slots,
            'cells' => $cells,
            'records_count' => $routines->count(),
        ];
    }

    public function schoolName(): string
    {
        $tenant = function_exists('tenant') ? tenant() : null;
        return (string) ($tenant?->school_name ?? config('app.name', 'School'));
    }
}
