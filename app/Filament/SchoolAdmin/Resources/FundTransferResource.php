<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\FundTransferResource\Pages;
use App\Models\Tenant\FundTransfer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FundTransferResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_accounts';

    protected static ?string $model = FundTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Fund Transfer';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Transfer')
                ->columns(2)
                ->schema([
                    Select::make('from_bank_account_id')
                        ->label('From account')
                        ->relationship('fromAccount', 'name')
                        ->searchable()->preload()->required(),
                    Select::make('to_bank_account_id')
                        ->label('To account')
                        ->relationship('toAccount', 'name')
                        ->searchable()->preload()->required()
                        ->rules(['different:from_bank_account_id']),
                    TextInput::make('amount_paisas')
                        ->label('Amount (PKR)')
                        ->numeric()->required()->minValue(1)
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                    DatePicker::make('transfer_date')->default(now())->required(),
                    TextInput::make('reference_number')->maxLength(255),
                    Textarea::make('note')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_date')->date('d M Y')->sortable(),
                TextColumn::make('fromAccount.name')->label('From'),
                TextColumn::make('toAccount.name')->label('To'),
                TextColumn::make('amount_paisas')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('reference_number')->placeholder('—')->toggleable(),
            ])
            ->defaultSort('transfer_date', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFundTransfers::route('/'),
            'create' => Pages\CreateFundTransfer::route('/create'),
            'edit'   => Pages\EditFundTransfer::route('/{record}/edit'),
        ];
    }
}
