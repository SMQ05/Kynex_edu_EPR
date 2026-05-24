<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\WalletResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Ledger';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'credit' ? 'success' : 'danger'),
                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2)),
                TextColumn::make('balance_after_paisas')
                    ->label('Balance after')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2)),
                TextColumn::make('source')->badge(),
                TextColumn::make('reference')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
