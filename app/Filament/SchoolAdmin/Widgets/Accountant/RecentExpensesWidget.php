<?php

namespace App\Filament\SchoolAdmin\Widgets\Accountant;

use App\Models\Tenant\Expense;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentExpensesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Recent Expenses';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Expense::query()
                    ->latest('expense_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Expense')
                    ->limit(30),

                TextColumn::make('category.name')
                    ->label('Category'),

                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 2)),

                TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof \BackedEnum ? $state->value : $state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
