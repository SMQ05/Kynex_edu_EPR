<?php

namespace App\Filament\SchoolAdmin\Widgets\ParentRole;

use App\Models\Tenant\Student;
use App\Models\Tenant\StudentGuardian;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ParentChildrenWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'My Children';

    public function table(Table $table): Table
    {
        $childrenIds = StudentGuardian::where('school_user_id', auth()->guard('school_users')->id())
            ->pluck('student_id');

        return $table
            ->query(
                Student::query()->whereIn('id', $childrenIds)
            )
            ->columns([
                TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn ($record) => $record->first_name . ' ' . $record->last_name),

                TextColumn::make('schoolClass.name')
                    ->label('Class'),

                TextColumn::make('section.name')
                    ->label('Section'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof \BackedEnum ? $state->value : $state) {
                        'enrolled' => 'success',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
