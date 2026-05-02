<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\FeeTypeResource\Pages;
use App\Models\Tenant\FeeType;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeeTypeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_fee_structures';

    protected static ?string $model = FeeType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees & Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fee Type Details')
                    ->schema([
                        Select::make('fee_group_id')
                            ->relationship('feeGroup', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('is_recurring')
                            ->label('Recurring Fee')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('feeGroup.name')
                    ->label('Fee Group')
                    ->sortable(),

                IconColumn::make('is_recurring')
                    ->boolean()
                    ->label('Recurring'),

                TextColumn::make('fee_masters_count')
                    ->counts('feeMasters')
                    ->label('Fee Masters')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('fee_group_id')
                    ->relationship('feeGroup', 'name')
                    ->label('Fee Group'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeeTypes::route('/'),
            'create' => Pages\CreateFeeType::route('/create'),
            'edit' => Pages\EditFeeType::route('/{record}/edit'),
        ];
    }
}
