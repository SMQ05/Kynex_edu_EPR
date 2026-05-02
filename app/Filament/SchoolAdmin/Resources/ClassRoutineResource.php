<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\DayOfWeek;
use App\Filament\SchoolAdmin\Resources\ClassRoutineResource\Pages;
use App\Models\Tenant\ClassRoutine;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class ClassRoutineResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_timetable';

    protected static ?string $model = ClassRoutine::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Class Routines';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 6;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Schedule Details')
                ->columns(2)
                ->schema([
                    Components\Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->relationship('academicYear', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Components\Select::make('class_id')
                        ->label('Class')
                        ->relationship('schoolClass', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),

                    Components\Select::make('section_id')
                        ->label('Section')
                        ->relationship('section', 'name', fn ($query, $get) => $query->when(
                            $get('class_id'),
                            fn ($q, $classId) => $q->where('class_id', $classId),
                        ))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Components\Select::make('day_of_week')
                        ->label('Day')
                        ->options(DayOfWeek::class)
                        ->required(),

                    Components\TextInput::make('period_number')
                        ->label('Period #')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(15)
                        ->required(),

                    Components\Toggle::make('is_break')
                        ->label('Break Period')
                        ->default(false)
                        ->reactive(),

                    Components\TextInput::make('break_label')
                        ->label('Break Label')
                        ->maxLength(50)
                        ->placeholder('e.g. Lunch Break')
                        ->visible(fn ($get): bool => (bool) $get('is_break')),

                    Components\Select::make('subject_id')
                        ->label('Subject')
                        ->relationship('subject', 'name')
                        ->searchable()
                        ->preload()
                        ->hidden(fn ($get): bool => (bool) $get('is_break'))
                        ->required(fn ($get): bool => ! (bool) $get('is_break')),

                    Components\Select::make('teacher_id')
                        ->label('Teacher')
                        ->relationship('teacher', 'name')
                        ->searchable()
                        ->preload()
                        ->hidden(fn ($get): bool => (bool) $get('is_break'))
                        ->nullable(),

                    Components\TextInput::make('room_number')
                        ->label('Room')
                        ->maxLength(50)
                        ->nullable(),

                    Components\TimePicker::make('start_time')
                        ->required()
                        ->seconds(false),

                    Components\TimePicker::make('end_time')
                        ->required()
                        ->seconds(false)
                        ->after('start_time'),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section')
                    ->sortable(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Day')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_number')
                    ->label('Period')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('End')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->placeholder('— Break —')
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_break')
                    ->label('Break')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('period_number')
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Academic Year')
                    ->relationship('academicYear', 'name'),

                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name'),

                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label('Day')
                    ->options(DayOfWeek::class),
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

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassRoutines::route('/'),
            'create' => Pages\CreateClassRoutine::route('/create'),
            'edit' => Pages\EditClassRoutine::route('/{record}/edit'),
        ];
    }
}
