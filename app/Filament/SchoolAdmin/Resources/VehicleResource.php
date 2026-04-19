<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Models\Tenant\Vehicle;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class VehicleResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_vehicles';

    protected static ?string $model = Vehicle::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    protected static string | \UnitEnum | null $navigationGroup = 'Transport';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vehicle Details')->schema([
                TextInput::make('vehicle_number')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),

                Select::make('vehicle_type')
                    ->options([
                        'bus' => 'Bus',
                        'van' => 'Van',
                        'car' => 'Car',
                        'coaster' => 'Coaster',
                    ])
                    ->default('bus')
                    ->required(),

                TextInput::make('make')
                    ->maxLength(100)
                    ->placeholder('e.g. Toyota, Hino'),

                TextInput::make('model')
                    ->maxLength(100),

                TextInput::make('year')
                    ->numeric()
                    ->minValue(1990)
                    ->maxValue(2030),

                TextInput::make('seating_capacity')
                    ->numeric()
                    ->required()
                    ->default(30),

                Select::make('fuel_type')
                    ->options([
                        'diesel' => 'Diesel',
                        'petrol' => 'Petrol',
                        'cng' => 'CNG',
                        'electric' => 'Electric',
                    ]),
            ])->columns(2),

            Section::make('Driver Information')->schema([
                TextInput::make('driver_name')
                    ->maxLength(255),

                TextInput::make('driver_phone')
                    ->tel()
                    ->maxLength(20),

                TextInput::make('driver_license')
                    ->maxLength(50),
            ])->columns(3),

            Section::make('Documents & Tracking')->schema([
                TextInput::make('gps_device_id')
                    ->label('GPS Device ID')
                    ->maxLength(100),

                TextInput::make('insurance_number')
                    ->maxLength(100),

                DatePicker::make('insurance_expiry'),

                DatePicker::make('fitness_expiry'),

                Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('vehicle_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('make')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('seating_capacity')
                    ->sortable(),

                Tables\Columns\TextColumn::make('driver_name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('driver_phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('insurance_expiry')
                    ->date()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->options([
                        'bus' => 'Bus',
                        'van' => 'Van',
                        'car' => 'Car',
                        'coaster' => 'Coaster',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                EditAction::make(),
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
            'index'  => VehicleResource\Pages\ListVehicles::route('/'),
            'create' => VehicleResource\Pages\CreateVehicle::route('/create'),
            'edit'   => VehicleResource\Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
