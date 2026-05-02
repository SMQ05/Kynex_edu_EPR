<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Models\Tenant\TransportRoute;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class TransportRouteResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_routes';

    protected static ?string $model = TransportRoute::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static string | \UnitEnum | null $navigationGroup = 'Transport';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Routes';

    protected static ?string $modelLabel = 'Route';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Route Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Route A - Gulberg to School'),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),

                Select::make('vehicle_id')
                    ->relationship('vehicle', 'vehicle_number')
                    ->searchable()
                    ->preload(),

                TextInput::make('fare_paisas')
                    ->label('Monthly Fare (Paisas)')
                    ->numeric()
                    ->default(0)
                    ->helperText('100 paisas = PKR 1'),

                TimePicker::make('departure_time'),

                TimePicker::make('arrival_time'),

                TextInput::make('distance_km')
                    ->label('Distance (km)')
                    ->numeric()
                    ->step(0.1),

                Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle.vehicle_number')
                    ->label('Vehicle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fare_paisas')
                    ->label('Fare')
                    ->formatStateUsing(fn (int $state): string => 'PKR ' . number_format($state / 100, 0))
                    ->sortable(),

                Tables\Columns\TextColumn::make('departure_time')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('arrival_time')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('distance_km')
                    ->suffix(' km')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('stops_count')
                    ->counts('stops')
                    ->label('Stops'),

                Tables\Columns\TextColumn::make('assignments_count')
                    ->counts('assignments')
                    ->label('Students'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
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
            'index'  => TransportRouteResource\Pages\ListTransportRoutes::route('/'),
            'create' => TransportRouteResource\Pages\CreateTransportRoute::route('/create'),
            'edit'   => TransportRouteResource\Pages\EditTransportRoute::route('/{record}/edit'),
        ];
    }
}
