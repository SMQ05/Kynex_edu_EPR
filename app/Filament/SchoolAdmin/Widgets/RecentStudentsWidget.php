<?php

namespace App\Filament\SchoolAdmin\Widgets;

use App\Models\Tenant\Student;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentStudentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Recently Enrolled Students';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(fn (Student $record): string => $record->first_name . ' ' . $record->last_name),

                TextColumn::make('schoolClass.name')
                    ->label('Class'),

                TextColumn::make('section.name')
                    ->label('Section'),

                TextColumn::make('created_at')
                    ->label('Enrolled')
                    ->since(),
            ])
            ->paginated(false);
    }
}
