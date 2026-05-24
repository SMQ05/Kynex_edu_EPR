<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CmsMenuResource\RelationManagers;

use App\Models\Tenant\CmsMenuItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Menu Items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->required()->maxLength(255),
            TextInput::make('url')
                ->maxLength(500)
                ->helperText('Relative path (e.g. /about) or full URL.'),
            Select::make('parent_id')
                ->label('Parent item')
                ->options(fn (Get $get, ?CmsMenuItem $record): array => $this->getOwnerRecord()
                    ->items()
                    ->whereNull('parent_id')
                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                    ->pluck('label', 'id')
                    ->all())
                ->searchable()
                ->placeholder('— Top level —')
                ->native(false),
            Select::make('target')
                ->options(['_self' => 'Same tab', '_blank' => 'New tab'])
                ->default('_self')
                ->native(false),
            TextInput::make('sort')->numeric()->default(0)->label('Sort order'),
            Toggle::make('is_active')->label('Active')->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable()
                    ->formatStateUsing(fn (CmsMenuItem $record): string => ($record->parent_id ? '— ' : '') . $record->label),
                TextColumn::make('url')->limit(40)->placeholder('—'),
                TextColumn::make('parent.label')->label('Parent')->placeholder('Top level')->toggleable(),
                TextColumn::make('sort')->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->defaultSort('sort')
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
