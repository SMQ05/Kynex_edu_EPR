<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\InventoryTransactionResource\Pages;
use App\Models\Tenant\InventoryTransaction;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;

class InventoryTransactionResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_inventory';

    protected static ?string $model = InventoryTransaction::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock Movement';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Transaction Details')->schema([
                Select::make('transaction_type')
                    ->options([
                        'receive' => 'Receive Stock',
                        'issue' => 'Issue Stock',
                        'return' => 'Return Stock',
                        'adjust' => 'Adjustment',
                        'write_off' => 'Write Off',
                    ])
                    ->required()
                    ->reactive(),

                Select::make('item_id')
                    ->label('Item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->helperText('For issues and write-offs, enter positive number (will be stored as negative)'),

                TextInput::make('unit_price_pkr')
                    ->label('Unit Price (PKR)')
                    ->numeric()
                    ->prefix('PKR')
                    ->visible(fn ($get) => in_array($get('transaction_type'), ['receive', null]))
                    ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                    ->formatStateUsing(fn ($state, $record) => $record ? ($record->unit_price_paisas ?? 0) / 100 : 0),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('transaction_type') === 'receive')
                    ->nullable(),

                Select::make('issued_to_user_id')
                    ->label('Issued To (User)')
                    ->relationship('issuedToUser', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('transaction_type') === 'issue')
                    ->nullable(),

                TextInput::make('issued_to_department')
                    ->label('Issued To (Department)')
                    ->visible(fn ($get) => $get('transaction_type') === 'issue')
                    ->maxLength(255),

                TextInput::make('reference_number')
                    ->maxLength(255),

                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item.name')->label('Item')->searchable(),
                TextColumn::make('transaction_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'receive' => 'success',
                        'issue' => 'warning',
                        'return' => 'info',
                        'adjust' => 'primary',
                        'write_off' => 'danger',
                    }),
                TextColumn::make('quantity')
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state) => $state >= 0 ? "+{$state}" : (string) $state),
                TextColumn::make('supplier.name')->label('Supplier')->placeholder('-'),
                TextColumn::make('issuedToUser.name')->label('Issued To')->placeholder('-'),
                TextColumn::make('reference_number')->placeholder('-'),
                TextColumn::make('recorder.name')->label('Recorded By'),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options([
                        'receive' => 'Receive',
                        'issue' => 'Issue',
                        'return' => 'Return',
                        'adjust' => 'Adjust',
                        'write_off' => 'Write Off',
                    ]),
                Tables\Filters\SelectFilter::make('item_id')
                    ->relationship('item', 'name')
                    ->label('Item'),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // recorded_by is injected by CreateInventoryTransaction::mutateFormDataBeforeCreate

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryTransactions::route('/'),
            'create' => Pages\CreateInventoryTransaction::route('/create'),
            'edit' => Pages\EditInventoryTransaction::route('/{record}/edit'),
        ];
    }
}
