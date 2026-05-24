<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentResource\Pages;

use App\Filament\SchoolAdmin\Resources\StudentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    /** Status buckets for the Unassigned / Disabled tabs. */
    private const UNASSIGNED = ['pending_admission', 'applicant', 'entry_test_pending'];
    private const INACTIVE   = ['left', 'graduated', 'expelled', 'suspended', 'rejected'];

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'enrolled' => Tab::make('Enrolled')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'enrolled'))
                ->badge(fn (): int => $this->countFor(fn (Builder $q) => $q->where('status', 'enrolled'))),
            'unassigned' => Tab::make('Unassigned')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', self::UNASSIGNED))
                ->badge(fn (): int => $this->countFor(fn (Builder $q) => $q->whereIn('status', self::UNASSIGNED))),
            'disabled' => Tab::make('Disabled')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', self::INACTIVE))
                ->badge(fn (): int => $this->countFor(fn (Builder $q) => $q->whereIn('status', self::INACTIVE))),
        ];
    }

    /** Count respecting the resource's base (campus-scoped) query. */
    private function countFor(\Closure $modifier): int
    {
        $query = StudentResource::getEloquentQuery();
        $modifier($query);

        return $query->count();
    }

    private function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'students-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Admission No', 'First Name', 'Last Name', 'Class', 'Section', 'Roll', 'Status']);

            StudentResource::getEloquentQuery()
                ->with(['schoolClass:id,name', 'section:id,name'])
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $s) {
                        $status = $s->status instanceof \BackedEnum ? $s->status->value : (string) $s->status;
                        fputcsv($out, [
                            $s->admission_number,
                            $s->first_name,
                            $s->last_name,
                            $s->schoolClass?->name,
                            $s->section?->name,
                            $s->roll_number,
                            $status,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
