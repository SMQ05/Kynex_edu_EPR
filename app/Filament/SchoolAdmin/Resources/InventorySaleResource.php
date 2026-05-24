<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\InventorySaleResource\Pages;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\InventorySale;
use App\Models\Tenant\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Inventory Item Sell. Records a sale of stock to a student/staff/other.
 * Creating a sale writes a paired negative inventory_transaction (type
 * "sell"), which decrements stock via InventoryTransaction's booted hook.
 */
class InventorySaleResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_inventory';

    protected static ?string $model = InventorySale::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Item Sell';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Sale')
                ->columns(2)
                ->schema([
                    Select::make('item_id')
                        ->label('Item')
                        ->relationship('item', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        // Item/qty/price are locked after creation: stock was
                        // already adjusted via the paired transaction.
                        ->disabledOn('edit')
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $item = $state ? InventoryItem::find($state) : null;
                            if ($item) {
                                $set('unit_price_pkr', round((int) $item->unit_price_paisas / 100, 2));
                            }
                        })
                        ->helperText(fn (Get $get): string => static::stockHint($get('item_id'))),
                    TextInput::make('quantity')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->reactive()
                        ->disabledOn('edit')
                        ->afterStateUpdated(fn ($state, Get $get, Set $set) => static::recalcTotal($get, $set)),
                    TextInput::make('unit_price_pkr')
                        ->label('Unit price (PKR)')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->prefix('PKR')
                        ->required()
                        ->reactive()
                        ->disabledOn('edit')
                        ->formatStateUsing(fn ($state, ?InventorySale $record) => $record ? round((int) $record->unit_price_paisas / 100, 2) : $state)
                        ->afterStateUpdated(fn ($state, Get $get, Set $set) => static::recalcTotal($get, $set)),
                    TextInput::make('total_pkr')
                        ->label('Total (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state, ?InventorySale $record) => $record ? round((int) $record->total_paisas / 100, 2) : $state)
                        ->helperText('Quantity × unit price.'),
                    Select::make('buyer_type')
                        ->options(InventorySale::BUYER_TYPES)
                        ->default('student')
                        ->required()
                        ->reactive()
                        ->disabledOn('edit'),
                    Select::make('student_id')
                        ->label('Student')
                        ->options(fn () => Student::query()->orderBy('first_name')->limit(1000)->get()
                            ->mapWithKeys(fn (Student $s) => [$s->id => trim($s->full_name . ' · ' . ($s->admission_number ?? '—'))])
                            ->toArray())
                        ->searchable()
                        ->visible(fn (Get $get) => $get('buyer_type') === 'student')
                        ->required(fn (Get $get) => $get('buyer_type') === 'student'),
                    Select::make('staff_user_id')
                        ->label('Staff member')
                        ->relationship('staff', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('buyer_type') === 'staff')
                        ->required(fn (Get $get) => $get('buyer_type') === 'staff'),
                    TextInput::make('buyer_name')
                        ->label('Buyer name')
                        ->visible(fn (Get $get) => $get('buyer_type') === 'other')
                        ->required(fn (Get $get) => $get('buyer_type') === 'other')
                        ->maxLength(255),
                    DatePicker::make('sold_on')->default(now())->required(),
                    TextInput::make('reference')->maxLength(255),
                    Textarea::make('notes')->rows(2)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sold_on')->date('d M Y')->sortable(),
                TextColumn::make('item.name')->label('Item')->searchable()->sortable(),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('buyer_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => InventorySale::BUYER_TYPES[$state] ?? $state),
                TextColumn::make('buyer_label')->label('Buyer')->getStateUsing(fn (InventorySale $r) => $r->buyer_label),
                TextColumn::make('total_paisas')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('reference')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('item_id')->relationship('item', 'name')->label('Item'),
                SelectFilter::make('buyer_type')->options(InventorySale::BUYER_TYPES),
            ])
            ->defaultSort('sold_on', 'desc');
    }

    protected static function recalcTotal(Get $get, Set $set): void
    {
        $qty = (int) ($get('quantity') ?? 0);
        $unit = (float) ($get('unit_price_pkr') ?? 0);
        $set('total_pkr', round($qty * $unit, 2));
    }

    protected static function stockHint(?string $itemId): string
    {
        if (! $itemId) {
            return 'Pick an item to see available stock.';
        }
        $item = InventoryItem::find($itemId);

        return $item ? "In stock: {$item->current_quantity} {$item->unit}" : '';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInventorySales::route('/'),
            'create' => Pages\CreateInventorySale::route('/create'),
            'edit'   => Pages\EditInventorySale::route('/{record}/edit'),
        ];
    }
}
