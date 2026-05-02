<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class InvoicesDueWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Invoices Due';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->with('tenant')
                    ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
                    ->orderBy('period_end', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tenant.school_name')
                    ->label('School')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('period')
                    ->label('Period')
                    ->getStateUsing(fn (Invoice $record): string => $record->period_start->format('M Y')),

                Tables\Columns\TextColumn::make('total_paisas')
                    ->label('Total PKR')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 0))
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(function (Invoice $record): string {
                        $daysOverdue = (int) Carbon::now()->diffInDays($record->period_end, false);

                        if ($daysOverdue >= 0) {
                            return '—';
                        }

                        return abs($daysOverdue) . ' days';
                    })
                    ->color(fn (Invoice $record): string => Carbon::now()->gt($record->period_end) ? 'danger' : 'gray')
                    ->alignEnd(),
            ])
            ->defaultPaginationPageOption(10);
    }
}
