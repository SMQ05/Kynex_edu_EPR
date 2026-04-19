<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\HostelBuildingResource\Pages;
use App\Models\SchoolUser;
use App\Models\Tenant\HostelBuilding;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;

class HostelBuildingResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_hostel_buildings';

    protected static ?string $model = HostelBuilding::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static string | \UnitEnum | null $navigationGroup = 'Hostel';

    protected static ?string $navigationLabel = 'Buildings';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Building Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->options([
                        'boys' => 'Boys',
                        'girls' => 'Girls',
                        'mixed' => 'Mixed',
                    ])
                    ->default('boys')
                    ->required(),

                Textarea::make('address')
                    ->rows(2)
                    ->columnSpanFull(),

                TextInput::make('total_floors')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(20),

                Select::make('warden_id')
                    ->label('Warden')
                    ->options(function () {
                        try {
                            return SchoolUser::role('HOSTEL_WARDEN')->pluck('name', 'id');
                        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
                            return [];
                        }
                    })
                    ->searchable()
                    ->nullable(),

                Select::make('campus_id')
                    ->label('Campus')
                    ->relationship('campus', 'name')
                    ->searchable()
                    ->nullable(),

                Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'boys' => 'info',
                        'girls' => 'danger',
                        'mixed' => 'warning',
                    }),

                TextColumn::make('total_floors')
                    ->label('Floors'),

                TextColumn::make('warden.name')
                    ->label('Warden')
                    ->placeholder('—'),

                TextColumn::make('rooms_count')
                    ->counts('rooms')
                    ->label('Rooms'),

                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'boys' => 'Boys',
                        'girls' => 'Girls',
                        'mixed' => 'Mixed',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => Pages\ListHostelBuildings::route('/'),
            'create' => Pages\CreateHostelBuilding::route('/create'),
            'edit' => Pages\EditHostelBuilding::route('/{record}/edit'),
        ];
    }
}
