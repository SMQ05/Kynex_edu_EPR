<?php

namespace App\Filament\SchoolAdmin\Widgets\Hostel;

use App\Models\Tenant\HostelGatePass;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class HostelCheckedOutTodayWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Students Checked Out Today';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HostelGatePass::query()
                    ->where('status', 'out')
                    ->whereDate('out_date_time', today())
                    ->latest('out_date_time')
            )
            ->columns([
                TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->student?->first_name . ' ' . $record->student?->last_name),

                TextColumn::make('purpose')
                    ->label('Purpose')
                    ->limit(25),

                TextColumn::make('out_date_time')
                    ->label('Left At')
                    ->time('H:i'),

                TextColumn::make('expected_return_date_time')
                    ->label('Expected Back')
                    ->dateTime('d M H:i'),
            ])
            ->paginated(false);
    }
}
