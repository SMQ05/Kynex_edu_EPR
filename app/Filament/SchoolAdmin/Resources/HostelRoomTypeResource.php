<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\HostelRoomTypeResource\Pages;
use App\Models\Tenant\HostelRoomType;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;

class HostelRoomTypeResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_hostel_buildings';

    protected static ?string $model = HostelRoomType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Hostel';

    protected static ?string $navigationLabel = 'Room Types';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Room Type')->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('capacity')->numeric()->default(1)->minValue(1)->maxValue(20),
                Textarea::make('description')->rows(2)->nullable(),
                TextInput::make('monthly_fee_pkr')
                    ->label('Monthly Fee (PKR)')
                    ->numeric()
                    ->default(0)
                    ->prefix('PKR')
                    ->dehydrateStateUsing(fn ($state) => (int) (($state ?? 0) * 100))
                    ->formatStateUsing(fn ($state, $record) => $record ? $record->monthly_fee_paisas / 100 : 0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('capacity')->sortable(),
                TextColumn::make('monthly_fee_paisas')
                    ->label('Monthly Fee')
                    ->formatStateUsing(fn ($state) => 'PKR ' . number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('rooms_count')->counts('rooms')->label('Rooms'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelRoomTypes::route('/'),
            'create' => Pages\CreateHostelRoomType::route('/create'),
            'edit' => Pages\EditHostelRoomType::route('/{record}/edit'),
        ];
    }
}
