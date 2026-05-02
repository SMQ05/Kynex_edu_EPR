<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ClassSubjectResource\Pages;
use App\Models\SchoolUser;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section as SectionModel;
use App\Models\Tenant\Subject;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * ClassSubjectResource — manages the class_subjects table that maps a
 * subject (and optionally a teacher) to a class+section in an academic
 * year. The teacher dashboard counts these rows to show "My Classes",
 * so creating one here is what makes a teacher's dashboard non-empty
 * without requiring a timetable to exist yet.
 */
class ClassSubjectResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_subjects';

    protected static ?string $model = ClassSubject::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Teaching Assignments';

    protected static ?string $modelLabel = 'Teaching Assignment';

    protected static ?string $pluralModelLabel = 'Teaching Assignments';

    protected static ?string $slug = 'teaching-assignments';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Assignment')
                ->columns(2)
                ->schema([
                    Components\Select::make('class_id')
                        ->label('Class')
                        ->options(fn () => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('section_id', null)),

                    Components\Select::make('section_id')
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
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Leave blank to apply to every section in this class.'),

                    Components\Select::make('subject_id')
                        ->label('Subject')
                        ->options(fn () => Subject::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),

                    Components\Select::make('teacher_id')
                        ->label('Teacher')
                        ->options(fn () => SchoolUser::query()
                            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['TEACHER', 'EXAM_ADMIN']))
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Only users with the TEACHER role appear here. Leave blank to assign later.'),

                    Components\Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default(fn () => AcademicYear::current()->value('id')),

                    Components\Toggle::make('is_optional')
                        ->label('Optional Subject')
                        ->helperText('Mark optional if this subject is an elective rather than core.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label('Section')
                    ->placeholder('All sections')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->placeholder('Unassigned')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Academic Year')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_optional')
                    ->label('Optional')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name'),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Teacher')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Academic Year')
                    ->relationship('academicYear', 'name'),

                Tables\Filters\TernaryFilter::make('teacher_id')
                    ->label('Has teacher')
                    ->placeholder('Any')
                    ->trueLabel('Assigned')
                    ->falseLabel('Unassigned')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('teacher_id'),
                        false: fn ($q) => $q->whereNull('teacher_id'),
                    ),
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
            'index'  => Pages\ListClassSubjects::route('/'),
            'create' => Pages\CreateClassSubject::route('/create'),
            'edit'   => Pages\EditClassSubject::route('/{record}/edit'),
        ];
    }
}
