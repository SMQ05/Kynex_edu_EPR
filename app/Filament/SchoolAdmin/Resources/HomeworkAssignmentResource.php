<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\Pages;
use App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\RelationManagers;
use App\Models\SchoolUser;
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

    protected static string | \UnitEnum | null $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Homework';

    protected static ?string $pluralModelLabel = 'Homework';

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

                    Select::make('class_id')
                        ->label('Class')
                        ->options(fn () => SchoolClass::orderBy('sort_order')->pluck('name', 'id'))
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

                            return Section::where('class_id', $classId)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->reactive(),

                    Select::make('subject_id')
                        ->label('Subject')
                        ->options(fn () => Subject::orderBy('name')->pluck('name', 'id'))
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
                        ->default(fn () => auth()->id()),

                    DatePicker::make('due_date')
                        ->label('Due Date')
                        ->required()
                        ->native(false)
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
