<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\SyllabusStatus;
use App\Enums\TopicStatus;
use App\Models\Tenant\StudyMaterial;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\SyllabusResource\Pages;
use App\Filament\SchoolAdmin\Resources\SyllabusResource\RelationManagers;
use App\Models\SchoolUser;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section as SectionModel;
use App\Models\Tenant\Subject;
use App\Models\Tenant\Syllabus;
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

class SyllabusResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_syllabus';

    protected static ?string $model = Syllabus::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Syllabus & Lesson Plans';

    protected static ?string $modelLabel = 'Syllabus';

    protected static ?string $pluralModelLabel = 'Syllabi';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 7;

    /**
     * Teachers see only their own syllabi; admins see everything.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (static::actingAsTeacher()) {
            $query->where('teacher_id', auth()->guard('school_users')->id());
        }

        return $query;
    }

    protected static function actingAsTeacher(): bool
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return false;
        }
        $active = $user->active_role ?? $user->roles->first()?->name;
        return $active === 'TEACHER';
    }

    protected static function teacherAllowedTuples(): \Illuminate\Support\Collection
    {
        return ClassSubject::query()
            ->where('teacher_id', auth()->guard('school_users')->id())
            ->get(['class_id', 'section_id', 'subject_id']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Syllabus Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Components\Select::make('class_id')
                        ->label('Class')
                        ->options(function () {
                            if (static::actingAsTeacher()) {
                                $ids = static::teacherAllowedTuples()->pluck('class_id')->unique();
                                return SchoolClass::whereIn('id', $ids)->orderBy('sort_order')->pluck('name', 'id');
                            }
                            return SchoolClass::orderBy('sort_order')->pluck('name', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('section_id', null)),

                    Components\Select::make('section_id')
                        ->label('Section (optional)')
                        ->options(function (callable $get) {
                            $classId = $get('class_id');
                            if (! $classId) {
                                return [];
                            }
                            $query = SectionModel::where('class_id', $classId);
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
                        ->nullable(),

                    Components\Select::make('subject_id')
                        ->label('Subject')
                        ->options(function (callable $get) {
                            if (static::actingAsTeacher()) {
                                $tuples = static::teacherAllowedTuples();
                                $classId = $get('class_id');
                                if ($classId) {
                                    $tuples = $tuples->where('class_id', $classId);
                                }
                                $ids = $tuples->pluck('subject_id')->unique();
                                return Subject::whereIn('id', $ids)->orderBy('name')->pluck('name', 'id');
                            }
                            return Subject::orderBy('name')->pluck('name', 'id');
                        })
                        ->required(),

                    Components\Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                        ->required()
                        ->default(fn () => AcademicYear::current()->value('id')),

                    Components\Select::make('teacher_id')
                        ->label('Owning Teacher')
                        ->options(function () {
                            try {
                                return SchoolUser::role('TEACHER', 'school_users')
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
                                return [];
                            }
                        })
                        ->searchable()
                        ->disabled(fn () => static::actingAsTeacher())
                        ->dehydrated()
                        ->default(fn () => auth()->guard('school_users')->id()),

                    Components\Select::make('status')
                        ->options(SyllabusStatus::options())
                        ->default(SyllabusStatus::Draft->value)
                        ->required(),

                    Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->sortable(),
                Tables\Columns\TextColumn::make('section.name')->label('Section')->placeholder('All sections'),
                Tables\Columns\TextColumn::make('subject.name')->label('Subject')->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')->label('Teacher')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('academicYear.name')->label('Year')->toggleable(),
                Tables\Columns\TextColumn::make('topics_count')
                    ->label('Units')
                    ->counts('topics')
                    ->badge(),

                // Coverage is the only number that answers "am I on track",
                // which is the question a scheme of work exists to answer. A
                // bare topic count cannot distinguish a course that is finished
                // from one that has not started.
                Tables\Columns\TextColumn::make('coverage')
                    ->label('Coverage')
                    ->state(function ($record): string {
                        $total = $record->topics()->count();

                        if ($total === 0) {
                            return '—';
                        }

                        $done = $record->topics()->where('status', TopicStatus::Completed)->count();

                        return round($done / $total * 100) . '%  (' . $done . '/' . $total . ')';
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $total = $record->topics()->count();

                        if ($total === 0) {
                            return 'gray';
                        }

                        $done = $record->topics()->where('status', TopicStatus::Completed)->count();
                        $pct = $done / $total * 100;

                        return match (true) {
                            $pct >= 66 => 'success',
                            $pct >= 25 => 'info',
                            default => 'warning',
                        };
                    }),

                Tables\Columns\TextColumn::make('current_unit')
                    ->label('Teaching now')
                    ->state(fn ($record) => $record->topics()
                        ->where('status', TopicStatus::InProgress)
                        ->orderBy('sort_order')
                        ->value('title') ?? '—')
                    ->wrap()
                    ->description(fn ($record) => ($week = $record->topics()
                        ->where('status', TopicStatus::InProgress)
                        ->orderBy('sort_order')
                        ->value('week_number')) ? 'Week ' . $week : null)
                    ->limit(40),

                Tables\Columns\TextColumn::make('materials')
                    ->label('Material')
                    ->state(fn ($record) => StudyMaterial::query()
                        ->whereIn('syllabus_topic_id', $record->topics()->select('id'))
                        ->count())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->tooltip('Recorded lectures attached to units on this plan')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof SyllabusStatus ? $state->label() : (SyllabusStatus::tryFrom((string) $state)?->label() ?? '—'))
                    ->color(fn ($state): string => ($state instanceof SyllabusStatus ? $state : SyllabusStatus::tryFrom((string) $state))?->color() ?? 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')->label('Class')->relationship('schoolClass', 'name'),
                Tables\Filters\SelectFilter::make('subject_id')->label('Subject')->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(SyllabusStatus::options()),
                Tables\Filters\Filter::make('no_material')
                    ->label('No material attached')
                    ->query(fn ($query) => $query->whereDoesntHave(
                        'topics',
                        fn ($q) => $q->whereIn('id', StudyMaterial::query()->whereNotNull('syllabus_topic_id')->select('syllabus_topic_id')),
                    )),
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
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TopicsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSyllabi::route('/'),
            'create' => Pages\CreateSyllabus::route('/create'),
            'edit'   => Pages\EditSyllabus::route('/{record}/edit'),
        ];
    }
}
