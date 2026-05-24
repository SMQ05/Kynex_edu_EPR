<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionEnquiryResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowupsRelationManager extends RelationManager
{
    protected static string $relationship = 'followups';

    protected static ?string $title = 'Follow-ups';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('follow_up_date')->default(now())->required(),
            DatePicker::make('next_follow_up_date')->label('Next follow-up'),
            Textarea::make('response')->rows(3)->columnSpanFull(),
            Textarea::make('note')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('follow_up_date')->date('d M Y')->sortable(),
                TextColumn::make('response')->limit(50)->wrap(),
                TextColumn::make('next_follow_up_date')->date('d M Y')->placeholder('—'),
                TextColumn::make('creator.name')->label('By')->placeholder('—')->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record, $livewire): void {
                        if ($record->next_follow_up_date) {
                            $livewire->getOwnerRecord()->update([
                                'next_follow_up_date' => $record->next_follow_up_date,
                            ]);
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('follow_up_date', 'desc');
    }
}
