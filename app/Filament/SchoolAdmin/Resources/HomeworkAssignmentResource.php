<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\AssignmentType;
use App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\Pages;
use App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\RelationManagers;
use App\Models\SchoolUser;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Subject;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class HomeworkAssignmentResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_classes';
    protected static string $rbacWritePermission = 'manage_homework';
    protected static ?string $model = HomeworkAssignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Assignments & Homework';

    protected static ?string $modelLabel = 'Assignment';

    protected static ?string $pluralModelLabel = 'Assignments & Homework';

    /**
     * Homework / classwork is a teacher-facing workflow. Hide from school
     * admins and institute heads — they review via reports, not by managing
     * rows directly.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }

        $role = (string) ($user->active_role ?? $user->roles->first()?->name ?? '');
        return in_array($role, ['TEACHER', 'EXAM_ADMIN'], true);
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    /**
     * When the logged-in user is acting as a TEACHER, scope the homework list
     * to homework they own. Admin and higher-rank roles see everything.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (static::actingAsTeacher()) {
            $query->where('teacher_id', auth()->guard('school_users')->id());
        }

        return $query;
    }

    /**
     * True when the current school user's active_role is TEACHER and they
     * don't also hold an admin-level role that should see the full data set.
     */
    protected static function actingAsTeacher(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }

        $adminRoles = ['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'];
        $active = $user->active_role ?? $user->roles->first()?->name;

        return $active === 'TEACHER' && ! collect($adminRoles)->contains(fn ($r) => $user->hasRole($r) && $active !== 'TEACHER');
    }

    /**
     * Returns the (class_id, section_id, subject_id) tuples a teacher has
     * been assigned to. Used to scope homework form dropdowns.
     */
    protected static function teacherAllowedTuples(): \Illuminate\Support\Collection
    {
        $userId = auth()->guard('school_users')->id();

        return ClassSubject::query()
            ->where('teacher_id', $userId)
            ->get(['class_id', 'section_id', 'subject_id']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Homework Details')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('type')
                        ->label('Type')
                        ->options(AssignmentType::options())
                        ->default(AssignmentType::Homework->value)
                        ->required(),

                    TextInput::make('total_marks')
                        ->label('Total Marks')
                        ->helperText('Optional. If set, submissions can be graded numerically and will count toward the weighted annual result.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1000),

                    Select::make('class_id')
                        ->label('Class')
                        ->options(function () {
                            if (static::actingAsTeacher()) {
                                $ids = static::teacherAllowedTuples()->pluck('class_id')->unique();
                                return SchoolClass::whereIn('id', $ids)
                                    ->orderBy('sort_order')
                                    ->pluck('name', 'id');
                            }
                            return SchoolClass::orderBy('sort_order')->pluck('name', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('section_id', null)),

                    Select::make('section_id')
                        ->label('Section')
                        ->options(function (callable $get) {
                            $classId = $get('class_id');
                            if (! $classId) {
                                return [];
                            }

                            $query = Section::where('class_id', $classId);

                            if (static::actingAsTeacher()) {
                                $allowed = static::teacherAllowedTuples()
                                    ->where('class_id', $classId)
                                    ->pluck('section_id')
                                    ->filter()
                                    ->unique();
                                if ($allowed->isNotEmpty()) {
                                    $query->whereIn('id', $allowed);
                                }
                            }

                            return $query->orderBy('name')->pluck('name', 'id');
                        })
                        ->required()
                        ->reactive(),

                    Select::make('subject_id')
                        ->label('Subject')
                        ->options(function (callable $get) {
                            if (static::actingAsTeacher()) {
                                $tuples = static::teacherAllowedTuples();
                                $classId = $get('class_id');
                                if ($classId) {
                                    $tuples = $tuples->where('class_id', $classId);
                                }
                                $subjectIds = $tuples->pluck('subject_id')->unique();
                                return Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'id');
                            }
                            return Subject::orderBy('name')->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable(),

                    Select::make('teacher_id')
                        ->label('Assigned By (Teacher)')
                        ->options(function () {
                            try {
                                return SchoolUser::role('TEACHER', 'school_users')
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
                                return [];
                            }
                        })
                        ->required()
                        ->searchable()
                        ->disabled(fn () => static::actingAsTeacher())
                        ->dehydrated()
                        ->default(fn () => auth()->guard('school_users')->id()),

                    DatePicker::make('due_date')
                        ->label('Due Date')
                        ->required()
                        
                        ->minDate(now()->subDay())
                        ->default(now()->addWeek()),

                    RichEditor::make('description')
                        ->label('Instructions / Description')
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'link',
                            'orderedList',
                            'bulletList',
                            'h2',
                            'h3',
                            'blockquote',
                        ]),
                ]),

            FormSection::make('Attachment')
                ->schema([
                    FileUpload::make('attachment_path')
                        ->label('Homework File (PDF, Doc, Image)')
                        ->directory('homework-attachments')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ])
                        ->maxSize(10240)
                        ->helperText('Max 10 MB. Accepts PDF, Word, JPEG, PNG, WebP.'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof AssignmentType ? $state->label() : (AssignmentType::tryFrom((string) $state)?->label() ?? '—'))
                    ->color(fn ($state): string => ($state instanceof AssignmentType ? $state : AssignmentType::tryFrom((string) $state))?->color() ?? 'gray'),

                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->sortable(),

                TextColumn::make('section.name')
                    ->label('Section')
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),

                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->color(fn (HomeworkAssignment $record): string => $record->due_date->isPast() ? 'danger' : 'success'),

                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_date', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(AssignmentType::options()),

                SelectFilter::make('class_id')
                    ->label('Class')
                    ->options(fn () => SchoolClass::orderBy('sort_order')->pluck('name', 'id')),

                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () => Subject::orderBy('name')->pluck('name', 'id')),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'upcoming' => 'Upcoming',
                        'overdue'  => 'Overdue',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'upcoming' => $query->where('due_date', '>=', now()->toDateString()),
                            'overdue'  => $query->where('due_date', '<', now()->toDateString()),
                            default    => $query,
                        };
                    }),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\HomeworkSubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHomeworkAssignments::route('/'),
            'create' => Pages\CreateHomeworkAssignment::route('/create'),
            'edit'   => Pages\EditHomeworkAssignment::route('/{record}/edit'),
        ];
    }
}
