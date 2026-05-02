<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\SyllabusResource\RelationManagers;

use App\Enums\TopicStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    protected static ?string $title = 'Topics';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('week_number')
                ->label('Week #')
                ->numeric()
                ->minValue(1)
                ->maxValue(60),

            DatePicker::make('planned_date')
                ->label('Planned Date'),

            Select::make('status')
                ->options(TopicStatus::options())
                ->default(TopicStatus::Planned->value)
                ->required(),

            DatePicker::make('completed_at')
                ->label('Completed On')
                ->visible(fn (callable $get) => $get('status') === TopicStatus::Completed->value),

            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),

            TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('week_number')->label('Week')->placeholder('—')->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('planned_date')->date()->placeholder('—')->sortable(),
                TextColumn::make('completed_at')->date()->placeholder('—')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TopicStatus ? $state->label() : (TopicStatus::tryFrom((string) $state)?->label() ?? '—'))
                    ->color(fn ($state): string => ($state instanceof TopicStatus ? $state : TopicStatus::tryFrom((string) $state))?->color() ?? 'gray'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('markCompleted')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== TopicStatus::Completed)
                    ->action(function ($record) {
                        $record->update([
                            'status'       => TopicStatus::Completed->value,
                            'completed_at' => now()->toDateString(),
                        ]);
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }
}
