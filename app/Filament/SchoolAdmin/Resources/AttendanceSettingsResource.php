<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\AttendanceSettingsResource\Pages;
use App\Models\Tenant\AttendanceSetting;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section as SectionModel;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

/**
 * AttendanceSettingsResource — Per-campus attendance timing configuration.
 *
 * Part 6c — Allows school admins to set late arrival cutoff, grace period,
 * and school start/end times per campus (or school-wide default).
 */
class AttendanceSettingsResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static ?string $model = AttendanceSetting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'Attendance Setting';

    protected static ?string $pluralModelLabel = 'Attendance Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campus & Timing')
                ->columns(2)
                ->schema([
                    Select::make('campus_id')
                        ->label('Campus (leave blank for school-wide default)')
                        ->options(fn () => Campus::orderBy('name')->pluck('name', 'id'))
                        ->nullable()
                        ->searchable()
                        ->helperText('Leave blank to set school-wide defaults'),

                    Select::make('class_id')
                        ->label('Class (optional)')
                        ->options(fn () => SchoolClass::orderBy('sort_order')->pluck('name', 'id'))
                        ->nullable()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('section_id', null))
                        ->helperText('Set class-specific cutoff times (overrides campus setting)'),

                    Select::make('section_id')
                        ->label('Section (optional)')
                        ->options(function (callable $get) {
                            $classId = $get('class_id');
                            if (! $classId) {
                                return [];
                            }
                            return SectionModel::where('class_id', $classId)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->nullable()
                        ->searchable()
                        ->reactive()
                        ->helperText('Leave blank for all sections in this class'),

                    TextInput::make('grace_period_minutes')
                        ->label('Grace Period (minutes)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(60)
                        ->default(0)
                        ->helperText('Extra minutes allowed after late_arrival_cutoff before marking late'),

                    TimePicker::make('school_start_time')
                        ->label('School Start Time')
                        ->seconds(false)
                        ->required(),

                    TimePicker::make('school_end_time')
                        ->label('School End Time')
                        ->seconds(false)
                        ->required(),

                    TimePicker::make('late_arrival_cutoff')
                        ->label('Late Arrival Cutoff')
                        ->seconds(false)
                        ->required()
                        ->helperText('Students arriving after this time are marked as late'),

                    TimePicker::make('half_day_cutoff')
                        ->label('Half-Day Cutoff')
                        ->seconds(false)
                        ->nullable()
                        ->helperText('Students arriving after this time may be counted as half-day'),

                    TimePicker::make('early_departure_cutoff')
                        ->label('Early Departure Cutoff')
                        ->seconds(false)
                        ->nullable()
                        ->helperText('Students leaving before this time are flagged for early departure'),
                ]),

            Section::make('Notifications')
                ->schema([
                    Toggle::make('notify_on_late_arrival')
                        ->label('Notify Parent on Late Arrival')
                        ->helperText('Send notification to guardian when student arrives late')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campus.name')
                    ->label('Campus')
                    ->default('School-Wide Default')
                    ->sortable(),

                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->default('All')
                    ->sortable(),

                TextColumn::make('section.name')
                    ->label('Section')
                    ->default('All')
                    ->sortable(),

                TextColumn::make('school_start_time')
                    ->label('Start')
                    ->sortable(),

                TextColumn::make('late_arrival_cutoff')
                    ->label('Late Cutoff')
                    ->sortable(),

                TextColumn::make('grace_period_minutes')
                    ->label('Grace (min)')
                    ->sortable(),

                IconColumn::make('notify_on_late_arrival')
                    ->label('Notify Late?')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAttendanceSettings::route('/'),
            'create' => Pages\CreateAttendanceSetting::route('/create'),
            'edit'   => Pages\EditAttendanceSetting::route('/{record}/edit'),
        ];
    }
}
