<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Multi-Class Student: additional class enrolments for a student beyond
 * their primary class/section.
 */
class ClassEnrolmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'classEnrolments';

    protected static ?string $title = 'Additional Class Enrolments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('class_id')
                ->label('Class')
                ->relationship('schoolClass', 'name')
                ->searchable()->preload()->required(),
            Select::make('section_id')
                ->label('Section')
                ->relationship('section', 'name')
                ->searchable()->preload(),
            Select::make('academic_year_id')
                ->label('Academic year')
                ->relationship('academicYear', 'name')
                ->searchable()->preload(),
            TextInput::make('roll_number')->maxLength(50),
            Toggle::make('is_primary')->label('Primary enrolment')->default(false),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schoolClass.name')->label('Class')->sortable(),
                TextColumn::make('section.name')->label('Section')->placeholder('—'),
                TextColumn::make('academicYear.name')->label('Year')->placeholder('—')->toggleable(),
                TextColumn::make('roll_number')->label('Roll')->placeholder('—'),
                IconColumn::make('is_primary')->boolean()->label('Primary'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
