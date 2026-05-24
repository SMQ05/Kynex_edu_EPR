<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentGroupResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Members';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('admission_number')->label('Adm #')->searchable(),
                TextColumn::make('full_name')->label('Student')
                    ->getStateUsing(fn ($record) => trim($record->first_name . ' ' . $record->last_name))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('schoolClass.name')->label('Class')->toggleable(),
                TextColumn::make('roll_number')->label('Roll')->toggleable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'admission_number'])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DetachBulkAction::make()]),
            ]);
    }
}
