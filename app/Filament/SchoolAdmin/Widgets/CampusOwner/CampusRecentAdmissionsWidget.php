<?php

namespace App\Filament\SchoolAdmin\Widgets\CampusOwner;

use App\Models\Tenant\Student;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CampusRecentAdmissionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Admissions (Your Campus)';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::query()
                    ->where('campus_id', auth('school_users')->user()?->campus_id)
                    ->latest()
                    ->limit(10)
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
                    ->color(fn (string $state) => match ($state) {
                        'enrolled' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Admitted')
                    ->since(),
            ])
            ->paginated(false);
    }
}
