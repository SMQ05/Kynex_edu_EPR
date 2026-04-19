<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\AttendanceDeviceResource\Pages;
use App\Models\Tenant\AttendanceDevice;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class AttendanceDeviceResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_integrations';

    protected static ?string $model = AttendanceDevice::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-device-tablet';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Attendance Devices';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Device Information')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('device_type')
                    ->options([
                        'zkteco' => 'ZKTeco Biometric',
                        'rfid'   => 'RFID Reader',
                        'manual' => 'Manual Entry',
                    ])
                    ->required()
                    ->default('manual'),

                TextInput::make('serial_number')
                    ->maxLength(100),

                TextInput::make('ip_address')
                    ->label('IP Address')
                    ->maxLength(45),

                TextInput::make('port')
                    ->numeric()
                    ->default(4370)
                    ->minValue(1)
                    ->maxValue(65535),

                TextInput::make('location')
                    ->maxLength(255),

                Select::make('campus_id')
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

                TextColumn::make('device_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'zkteco' => 'primary',
                        'rfid'   => 'info',
                        'manual' => 'gray',
                        default  => 'gray',
                    }),

                TextColumn::make('serial_number')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(),

                TextColumn::make('campus.name')
                    ->label('Campus')
                    ->sortable(),

                TextColumn::make('location')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('last_sync_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Sync')
                    ->placeholder('Never'),

                TextColumn::make('biometric_logs_count')
                    ->counts('biometricLogs')
                    ->label('Logs')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('device_type')
                    ->options([
                        'zkteco' => 'ZKTeco',
                        'rfid'   => 'RFID',
                        'manual' => 'Manual',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
            'index'  => Pages\ListAttendanceDevices::route('/'),
            'create' => Pages\CreateAttendanceDevice::route('/create'),
            'edit'   => Pages\EditAttendanceDevice::route('/{record}/edit'),
        ];
    }
}
