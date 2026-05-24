<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\LessonResource\Pages;
use App\Filament\SchoolAdmin\Resources\LessonResource\RelationManagers\LessonPlansRelationManager;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Subject;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LessonResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_lesson_plans';

    protected static ?string $model = Lesson::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    protected static ?string $navigationLabel = 'Lessons';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Lesson')
                ->columns(2)
                ->schema([
                    Select::make('class_id')
                        ->label('Class')
                        ->options(fn (): array => SchoolClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->required()->live(),
                    Select::make('subject_id')
                        ->label('Subject')
                        ->options(fn (): array => Subject::orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->required(),
                    Select::make('section_id')
                        ->label('Section (optional)')
                        ->options(fn (Get $get): array => $get('class_id')
                            ? Section::where('class_id', $get('class_id'))->orderBy('name')->pluck('name', 'id')->all()
                            : [])
                        ->searchable()->nullable(),
                    Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(fn (): array => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')->all())
                        ->searchable()->nullable(),
                    TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('code')->label('Lesson code')->maxLength(60),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40)->weight('semibold'),
                TextColumn::make('schoolClass.name')->label('Class')->badge()->placeholder('—'),
                TextColumn::make('subject.name')->label('Subject')->placeholder('—'),
                TextColumn::make('section.name')->label('Section')->placeholder('—')->toggleable(),
                TextColumn::make('lesson_plans_count')->counts('lessonPlans')->label('Plans')->badge(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                SelectFilter::make('class_id')->relationship('schoolClass', 'name')->label('Class'),
                SelectFilter::make('subject_id')->relationship('subject', 'name')->label('Subject'),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LessonPlansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit'   => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
