<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\SectionResource\Pages;
use App\Models\SchoolUser;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\Section;
use App\Models\Tenant\Subject;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class SectionResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_sections';

    protected static ?string $model = Section::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Sections';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 4;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            FormSection::make('Section Details')
                ->columns(2)
                ->schema([
                    Components\Select::make('class_id')
                        ->label('Class')
                        ->relationship('schoolClass', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. A, B, Blue'),

                    Components\Select::make('class_teacher_id')
                        ->label('Class Teacher')
                        ->relationship('classTeacher', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Components\TextInput::make('capacity')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(500)
                        ->default(40)
                        ->required()
                        ->helperText('Maximum students this section can hold.'),

                    Components\TextInput::make('room_number')
                        ->label('Room Number')
                        ->maxLength(50)
                        ->nullable(),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classTeacher.name')
                    ->label('Class Teacher')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('room_number')
                    ->label('Room')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->counts('students')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name'),
            ])
            ->actions([
                EditAction::make(),

                Action::make('assignSubjects')
                    ->label('Assign Subjects')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->modalHeading(fn (Section $record) => "Assign subjects to {$record->schoolClass?->name} – {$record->name}")
                    ->modalSubmitActionLabel('Save assignments')
                    ->form([
                        Components\Select::make('academic_year_id')
                            ->label('Academic Year')
                            ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                            ->required()
                            ->default(fn () => AcademicYear::current()->value('id')),

                        Components\Repeater::make('rows')
                            ->label('Subjects')
                            ->schema([
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
                                    ->nullable(),

                                Components\Toggle::make('is_optional')
                                    ->label('Optional')
                                    ->inline(false),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add another subject'),
                    ])
                    ->action(function (Section $record, array $data) {
                        $created = 0;
                        $skipped = 0;
                        $yearId  = $data['academic_year_id'];

                        foreach ($data['rows'] ?? [] as $row) {
                            if (empty($row['subject_id'])) {
                                continue;
                            }

                            $exists = ClassSubject::where('class_id', $record->class_id)
                                ->where('section_id', $record->id)
                                ->where('subject_id', $row['subject_id'])
                                ->where('academic_year_id', $yearId)
                                ->exists();

                            if ($exists) {
                                $skipped++;
                                continue;
                            }

                            ClassSubject::create([
                                'class_id'         => $record->class_id,
                                'section_id'       => $record->id,
                                'subject_id'       => $row['subject_id'],
                                'teacher_id'       => $row['teacher_id'] ?? null,
                                'academic_year_id' => $yearId,
                                'is_optional'      => (bool) ($row['is_optional'] ?? false),
                            ]);
                            $created++;
                        }

                        Notification::make()
                            ->title('Assignments saved')
                            ->body(sprintf('Created %d, skipped %d duplicate.', $created, $skipped))
                            ->success()
                            ->send();
                    }),

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
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
        ];
    }
}
