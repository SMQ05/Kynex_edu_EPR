<?php

namespace App\Filament\SchoolAdmin\Widgets\Teacher;

use App\Models\Tenant\ClassRoutine;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TeacherTimetableWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = "Today's Timetable";

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClassRoutine::query()
                    ->where('teacher_id', auth()->id())
                    ->where('day_of_week', strtolower(now()->format('l')))
                    ->orderBy('start_time')
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

                TextColumn::make('room_number')
                    ->label('Room'),
            ])
            ->paginated(false);
    }
}
