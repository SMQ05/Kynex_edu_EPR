<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets\AttendanceClerk;

use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\SchoolClass;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AttendanceClerkClassListWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Classes — Today\'s Attendance';

    public function table(Table $table): Table
    {
        $today = now()->toDateString();

        return $table
            ->query(
                SchoolClass::query()
                    ->withCount([
                        'sections as total_students' => function ($query) {
                            $query->withCount('students');
                        },
                    ])
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Class')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sections_count')
                    ->label('Sections')
                    ->counts('sections'),

                Tables\Columns\TextColumn::make('marked_count')
                    ->label('Marked Today')
                    ->getStateUsing(function (SchoolClass $record) use ($today): int {
                        return AttendanceRecord::forDate($today)
                            ->forClass($record->id)
                            ->count();
                    }),

                Tables\Columns\TextColumn::make('absent_count')
                    ->label('Absent')
                    ->getStateUsing(function (SchoolClass $record) use ($today): int {
                        return AttendanceRecord::forDate($today)
                            ->forClass($record->id)
                            ->absent()
                            ->count();
                    })
                    ->color('danger'),
            ])
            ->actions([
                Action::make('markNow')
                    ->label('Mark Now')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (SchoolClass $record): string =>
                        // Links to a future Attendance marking page; stub URL for now
                        route('filament.schoolAdmin.pages.dashboard') . '?class=' . $record->id
                    ),
            ])
            ->paginated(false);
    }
}
