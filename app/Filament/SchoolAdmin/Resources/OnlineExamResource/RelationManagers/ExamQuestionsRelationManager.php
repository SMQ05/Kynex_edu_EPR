<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\OnlineExamResource\RelationManagers;

use App\Models\Tenant\ExamQuestion;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExamQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Exam Questions';

    public function form(Schema $schema): Schema
    {
        // Pivot edit form (marks override + sort).
        return $schema->components([
            TextInput::make('marks')
                ->numeric()->step('0.5')
                ->label('Marks (override)')
                ->helperText('Leave blank to use the question default.'),

            TextInput::make('sort_order')
                ->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('question_text')->limit(70)->wrap()->searchable(),
                TextColumn::make('group.name')->label('Group')->toggleable(),
                TextColumn::make('marks')
                    ->label('Default marks'),
                TextColumn::make('pivot.marks')
                    ->label('Used marks')
                    ->state(fn (ExamQuestion $record) => $record->pivot->marks ?? $record->marks),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'mcq'          => 'Multiple choice',
                    'true_false'   => 'True / False',
                    'short_answer' => 'Short answer',
                    'essay'        => 'Essay',
                    'math'         => 'Math',
                ]),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add from Question Bank')
                    ->recordSelectSearchColumns(['question_text'])
                    ->preloadRecordSelect()
                    ->multiple()
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('is_active', true))
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('marks')
                            ->numeric()->step('0.5')
                            ->label('Marks override (optional)'),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ])
                    ->after(fn () => $this->recompute()),
            ])
            ->actions([
                DetachAction::make()->after(fn () => $this->recompute()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->after(fn () => $this->recompute()),
                ]),
            ])
            ->defaultSort('online_exam_questions.sort_order');
    }

    protected function recompute(): void
    {
        $exam = $this->getOwnerRecord();
        if ($exam) {
            $exam->load('questions');
            $exam->recomputeTotalMarks();
        }
    }
}
