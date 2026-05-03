<?php

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\FeeGroupResource\Pages;
use App\Models\Tenant\FeeGroup;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeeGroupResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_fee_structures';

    protected static ?string $model = FeeGroup::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Fees';

    public static function shouldRegisterNavigation(): bool
    {
        // Fee Groups + Fee Types are now managed under "Fee Catalog".
        // Keep the resource accessible via direct URL for power users
        // but hide from the sidebar to keep the nav user-friendly.
        return false;
    }

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fee Group Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),
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

                TextColumn::make('fee_types_count')
                    ->counts('feeTypes')
                    ->label('Fee Types')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeeGroups::route('/'),
            'create' => Pages\CreateFeeGroup::route('/create'),
            'edit' => Pages\EditFeeGroup::route('/{record}/edit'),
        ];
    }
}
