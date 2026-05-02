<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\HostelRoomResource\Pages;
use App\Models\Tenant\HostelRoom;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;

class HostelRoomResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_hostel_rooms';

    protected static ?string $model = HostelRoom::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static string | \UnitEnum | null $navigationGroup = 'Hostel';

    protected static ?string $navigationLabel = 'Rooms';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Room Details')->schema([
                Select::make('building_id')
                    ->relationship('building', 'name')
                    ->required()
                    ->searchable(),

                Select::make('room_type_id')
                    ->relationship('roomType', 'name')
                    ->required()
                    ->searchable(),

                TextInput::make('room_number')
                    ->required()
                    ->maxLength(20),

                TextInput::make('floor_number')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                TextInput::make('total_beds')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),

                Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'full' => 'Full',
                        'maintenance' => 'Maintenance',
                        'closed' => 'Closed',
                    ])
                    ->default('available'),

                CheckboxList::make('facilities')
                    ->options([
                        'ac' => 'Air Conditioning',
                        'attached_bathroom' => 'Attached Bathroom',
                        'hot_water' => 'Hot Water',
                        'wifi' => 'WiFi',
                        'study_table' => 'Study Table',
                        'wardrobe' => 'Wardrobe',
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                TextInput::make('monthly_fee_pkr')
                    ->label('Monthly Fee Override (PKR)')
                    ->numeric()
                    ->nullable()
                    ->prefix('PKR')
                    ->helperText('Leave empty to use room type fee')
                    ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                    ->formatStateUsing(fn ($state, $record) => $record?->monthly_fee_paisas ? $record->monthly_fee_paisas / 100 : null),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('building.name')->sortable()->searchable(),
                TextColumn::make('room_number')->sortable()->searchable(),
                TextColumn::make('roomType.name')->label('Type'),
                TextColumn::make('floor_number')->label('Floor'),
                TextColumn::make('occupancy')
                    ->label('Beds')
                    ->state(fn ($record) => "{$record->occupied_beds}/{$record->total_beds}"),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'full' => 'danger',
                        'maintenance' => 'warning',
                        'closed' => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('building_id')
                    ->relationship('building', 'name')
                    ->label('Building'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'full' => 'Full',
                        'maintenance' => 'Maintenance',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('room_type_id')
                    ->relationship('roomType', 'name')
                    ->label('Type'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelRooms::route('/'),
            'create' => Pages\CreateHostelRoom::route('/create'),
            'edit' => Pages\EditHostelRoom::route('/{record}/edit'),
        ];
    }
}
