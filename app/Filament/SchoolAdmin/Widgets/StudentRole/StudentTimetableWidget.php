<?php

namespace App\Filament\SchoolAdmin\Widgets\StudentRole;

use App\Models\Tenant\ClassRoutine;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentTimetableWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = "Today's Timetable";

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    public function table(Table $table): Table
    {
        // Student sees their class timetable based on linked student record
        // For now, show all routines for today as a fallback
        return $table
            ->query(
                ClassRoutine::query()
                    ->where('day_of_week', strtolower(now()->format('l')))
                    ->orderBy('start_time')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('start_time')
                    ->label('Time')
                    ->formatStateUsing(fn ($state, $record) => substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5)),

                TextColumn::make('subject.name')
                    ->label('Subject'),

                TextColumn::make('schoolClass.name')
                    ->label('Class'),

                TextColumn::make('section.name')
                    ->label('Section'),

                TextColumn::make('teacher.name')
                    ->label('Teacher'),
            ])
            ->paginated(false);
    }
}
