<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ExamSeatPlanResource\Pages;
use App\Models\Tenant\ExamSeatPlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExamSeatPlanResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_exam_plan';

    protected static ?string $model = ExamSeatPlan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?string $navigationLabel = 'Seat Allocations';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'seat allocation';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Seat Allocation')->schema([
                Select::make('exam_id')
                    ->relationship('exam', 'name')
                    ->searchable()->preload()->required()->label('Exam'),

                Select::make('student_id')
                    ->relationship('student', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['first_name', 'last_name'])->required()->label('Student'),

                TextInput::make('room')->required()->maxLength(100),
                TextInput::make('seat_number')->maxLength(50),
                TextInput::make('row_number')->numeric()->minValue(1),
                TextInput::make('column_number')->numeric()->minValue(1),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('exam.name')->label('Exam')->sortable()->searchable(),
                TextColumn::make('room')->sortable()->searchable(),
                TextColumn::make('seat_number')->label('Seat')->sortable(),
                TextColumn::make('student.roll_number')->label('Roll')->toggleable(),
                TextColumn::make('student.full_name')->label('Student')->searchable(['first_name', 'last_name']),
                TextColumn::make('schoolClass.name')->label('Class')->toggleable(),
                TextColumn::make('row_number')->label('Row')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('column_number')->label('Col')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('exam_id')->relationship('exam', 'name')->label('Exam'),
                SelectFilter::make('room')
                    ->options(fn () => ExamSeatPlan::query()
                        ->distinct()
                        ->orderBy('room')
                        ->pluck('room', 'room')
                        ->all()),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('room');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExamSeatPlans::route('/'),
            'create' => Pages\CreateExamSeatPlan::route('/create'),
            'edit'   => Pages\EditExamSeatPlan::route('/{record}/edit'),
        ];
    }
}
