<?php

namespace App\Filament\SchoolAdmin\Widgets\Accountant;

use App\Models\Tenant\FeePayment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentFeeCollectionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Recent Fee Collections';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FeePayment::query()
                    ->latest('payment_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('studentFee.student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->studentFee?->student?->first_name . ' ' . $record->studentFee?->student?->last_name),

                TextColumn::make('total_amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 2)),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge(),

                TextColumn::make('payment_date')
                    ->label('Date')
                    ->dateTime('d M Y H:i'),
            ])
            ->paginated(false);
    }
}
