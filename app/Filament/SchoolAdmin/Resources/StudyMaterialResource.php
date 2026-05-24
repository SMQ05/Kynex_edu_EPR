<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\StudyMaterialResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\StudyMaterial;
use App\Models\Tenant\Subject;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiDraftService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudyMaterialResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_study_materials';

    protected static ?string $model = StudyMaterial::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    protected static ?string $navigationLabel = 'Study Materials';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Material')
                ->columns(2)
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('category')
                        ->options(StudyMaterial::CATEGORIES)
                        ->default('study_material')->required()->native(false),
                    Select::make('source_type')
                        ->label('Source')
                        ->options(['file' => 'File upload', 'link' => 'External link'])
                        ->default('file')->required()->live()->native(false),
                    FileUpload::make('file_path')
                        ->label('File')
                        ->directory('study-materials')
                        ->visible(fn (Get $get): bool => $get('source_type') === 'file')
                        ->required(fn (Get $get): bool => $get('source_type') === 'file'),
                    TextInput::make('external_url')
                        ->label('Link URL')->url()
                        ->visible(fn (Get $get): bool => $get('source_type') === 'link')
                        ->required(fn (Get $get): bool => $get('source_type') === 'link'),
                    Textarea::make('description')->rows(3)->columnSpanFull()
                        ->hintActions([
                            \App\Filament\SchoolAdmin\Support\AiActions::draftInto('description', [
                                'instruction'   => 'a short description of this study material for students',
                                'contextFields' => ['title' => 'Title'],
                                'feature'       => 'study_material_describe',
                            ]),
                            \App\Filament\SchoolAdmin\Support\AiActions::refineInto('description'),
                        ]),
                ]),
            FormSection::make('Audience & Scheduling')
                ->columns(2)
                ->schema([
                    Select::make('class_id')
                        ->label('Class')
                        ->options(fn (): array => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->nullable()->live(),
                    Select::make('section_id')
                        ->label('Section')
                        ->options(fn (Get $get): array => $get('class_id')
                            ? Section::where('class_id', $get('class_id'))->orderBy('name')->pluck('name', 'id')->all()
                            : [])
                        ->searchable()->nullable(),
                    Select::make('subject_id')
                        ->label('Subject')
                        ->options(fn (): array => Subject::orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->nullable(),
                    Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(fn (): array => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')->all())
                        ->searchable()->nullable(),
                    DatePicker::make('available_from')->label('Available from'),
                    Toggle::make('is_published')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40)->weight('semibold'),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StudyMaterial::CATEGORIES[$state] ?? $state),
                TextColumn::make('schoolClass.name')->label('Class')->placeholder('—')->toggleable(),
                TextColumn::make('subject.name')->label('Subject')->placeholder('—')->toggleable(),
                TextColumn::make('source_type')->label('Type')->badge(),
                TextColumn::make('download_count')->label('Downloads')->sortable()->toggleable(),
                IconColumn::make('is_published')->boolean()->label('Published'),
                TextColumn::make('created_at')->date('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->options(StudyMaterial::CATEGORIES),
                SelectFilter::make('class_id')->relationship('schoolClass', 'name')->label('Class'),
                SelectFilter::make('subject_id')->relationship('subject', 'name')->label('Subject'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('aiSummarize')
                    ->label('AI Summarize')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->action(fn (StudyMaterial $record) => static::runSummarize($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    /** 🤖 Draft a student-facing summary/blurb for a material from its title. */
    protected static function runSummarize(StudyMaterial $record): void
    {
        try {
            $text = app(AiDraftService::class)->draft(
                instruction: 'Write a one or two sentence student-friendly summary of this study material so learners know what it covers.',
                context: array_filter([
                    'Title'    => $record->title,
                    'Subject'  => $record->subject?->name,
                    'Class'    => $record->schoolClass?->name,
                    'Existing' => $record->description,
                ]),
                feature: 'study_material_summarize',
            );
            $record->update(['description' => $text]);
            Notification::make()->title('Summary added')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI summarize failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudyMaterials::route('/'),
            'create' => Pages\CreateStudyMaterial::route('/create'),
            'edit'   => Pages\EditStudyMaterial::route('/{record}/edit'),
        ];
    }
}
