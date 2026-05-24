<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\QuestionGroupResource\Pages;
use App\Filament\SchoolAdmin\Resources\QuestionGroupResource\RelationManagers\QuestionsRelationManager;
use App\Models\Tenant\QuestionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class QuestionGroupResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_question_bank';

    protected static ?string $model = QuestionGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?string $navigationLabel = 'Question Groups';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Question Group')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Class 8 Science — Cells'),

                Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Subject'),

                Select::make('class_id')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Class'),

                Toggle::make('is_active')
                    ->default(true),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('subject.name')->label('Subject')->sortable()->toggleable(),
                TextColumn::make('schoolClass.name')->label('Class')->sortable()->toggleable(),
                TextColumn::make('questions_count')->counts('questions')->label('Questions'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subject_id')->relationship('subject', 'name')->label('Subject'),
                SelectFilter::make('class_id')->relationship('schoolClass', 'name')->label('Class'),
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
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuestionGroups::route('/'),
            'create' => Pages\CreateQuestionGroup::route('/create'),
            'edit'   => Pages\EditQuestionGroup::route('/{record}/edit'),
        ];
    }
}
