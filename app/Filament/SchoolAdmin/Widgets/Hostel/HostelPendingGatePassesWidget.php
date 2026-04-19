<?php

namespace App\Filament\SchoolAdmin\Widgets\Hostel;

use App\Models\Tenant\HostelGatePass;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class HostelPendingGatePassesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Pending Gate Pass Approvals';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HostelGatePass::query()
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->student?->first_name . ' ' . $record->student?->last_name),

                TextColumn::make('purpose')
                    ->label('Purpose')
                    ->limit(30),

                TextColumn::make('out_date_time')
                    ->label('Leaving At')
                    ->dateTime('d M H:i'),

                TextColumn::make('expected_return_date_time')
                    ->label('Expected Return')
                    ->dateTime('d M H:i'),
            ])
            ->paginated(false);
    }
}
