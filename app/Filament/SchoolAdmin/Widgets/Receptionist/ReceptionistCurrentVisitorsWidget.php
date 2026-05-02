<?php

namespace App\Filament\SchoolAdmin\Widgets\Receptionist;

use App\Models\Tenant\Visitor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ReceptionistCurrentVisitorsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Current Visitors (Checked In)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Visitor::query()
                    ->where('status', 'checked_in')
                    ->latest('check_in_time')
            )
            ->columns([
                TextColumn::make('visitor_name')
                    ->label('Name')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone'),

                TextColumn::make('purpose')
                    ->label('Purpose')
                    ->limit(30),

                TextColumn::make('whom_to_meet')
                    ->label('Meeting'),

                TextColumn::make('badge_number')
                    ->label('Badge'),

                TextColumn::make('check_in_time')
                    ->label('Checked In')
                    ->dateTime('H:i'),

                TextColumn::make('vehicle_number')
                    ->label('Vehicle')
                    ->placeholder('—'),
            ])
            ->paginated(false);
    }
}
