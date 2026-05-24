<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\OnlineExamResource\Pages;
use App\Filament\SchoolAdmin\Resources\OnlineExamResource\RelationManagers\AttemptsRelationManager;
use App\Filament\SchoolAdmin\Resources\OnlineExamResource\RelationManagers\ExamQuestionsRelationManager;
use App\Models\Tenant\OnlineExam;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OnlineExamResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_online_exams';

    protected static ?string $model = OnlineExam::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?string $navigationLabel = 'Online Exams';

    protected static ?int $navigationSort = 22;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Online Exam')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Class 8 Science — Online Quiz 1'),

                Select::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Academic Year'),

                Select::make('class_id')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Class')
                    ->live(),

                Select::make('section_id')
                    ->relationship(
                        'section',
                        'name',
                        fn ($query, callable $get) => $get('class_id')
                            ? $query->where('class_id', $get('class_id'))
                            : $query,
                    )
                    ->searchable()
                    ->preload()
                    ->label('Section'),

                Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Subject'),

                Select::make('exam_id')
                    ->relationship('exam', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Linked Exam (optional)')
                    ->helperText('Optionally tie this online exam to a parent Exam.'),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('instructions')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Rules & Window')->schema([
                TextInput::make('duration_minutes')
                    ->numeric()->minValue(1)->default(60)->required()
                    ->suffix('minutes'),

                TextInput::make('passing_marks')
                    ->numeric()->minValue(0)->default(0)
                    ->helperText('Set to 0 for no pass threshold.'),

                TextInput::make('total_marks')
                    ->numeric()->minValue(0)->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Auto-computed from attached questions.'),

                DateTimePicker::make('window_opens_at')->label('Opens at'),
                DateTimePicker::make('window_closes_at')->label('Closes at')
                    ->afterOrEqual('window_opens_at'),

                Select::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                        'closed'    => 'Closed',
                    ])
                    ->default('draft')
                    ->required(),

                Toggle::make('shuffle_questions')->default(true),
                Toggle::make('show_score_to_student')
                    ->label('Show score to student after submit')
                    ->default(false),
                Toggle::make('ai_grade_enabled')
                    ->label('AI auto-grade short/essay answers')
                    ->helperText('Allowed for exams. Marks remain reviewable.')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('schoolClass.name')->label('Class')->toggleable(),
                TextColumn::make('subject.name')->label('Subject')->toggleable(),
                TextColumn::make('total_marks')->label('Marks')->sortable(),
                TextColumn::make('duration_minutes')->label('Mins')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'closed'    => 'gray',
                        default     => 'warning',
                    }),
                TextColumn::make('attempts_count')->counts('attempts')->label('Attempts'),
                IconColumn::make('ai_grade_enabled')->boolean()->label('AI grade')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('window_closes_at')->dateTime()->label('Closes')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft'     => 'Draft',
                    'published' => 'Published',
                    'closed'    => 'Closed',
                ]),
                SelectFilter::make('class_id')->relationship('schoolClass', 'name')->label('Class'),
                SelectFilter::make('subject_id')->relationship('subject', 'name')->label('Subject'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ExamQuestionsRelationManager::class,
            AttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOnlineExams::route('/'),
            'create' => Pages\CreateOnlineExam::route('/create'),
            'edit'   => Pages\EditOnlineExam::route('/{record}/edit'),
        ];
    }
}
