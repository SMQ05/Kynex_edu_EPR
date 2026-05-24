<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\OnlineExamResource\RelationManagers;

use App\Models\Tenant\OnlineExamAttempt;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\ExamQuestionAiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Attempts & Grading';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.roll_number')->label('Roll')->toggleable(),
                TextColumn::make('student.full_name')->label('Student')->searchable(['first_name', 'last_name']),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'graded'    => 'success',
                        'submitted' => 'warning',
                        'started'   => 'info',
                        'expired'   => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('obtained_marks')->label('Score')
                    ->state(fn (OnlineExamAttempt $r) => $r->obtained_marks === null
                        ? '—'
                        : rtrim(rtrim((string) $r->obtained_marks, '0'), '.') . ' / ' . rtrim(rtrim((string) $r->total_marks, '0'), '.')),
                TextColumn::make('percentage')->suffix('%')->toggleable(),
                IconColumn::make('needs_manual_grading')->boolean()->label('Needs grading'),
                TextColumn::make('submitted_at')->dateTime()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending'   => 'Pending',
                    'started'   => 'Started',
                    'submitted' => 'Submitted',
                    'graded'    => 'Graded',
                    'expired'   => 'Expired',
                ]),
            ])
            ->actions([
                Action::make('aiGrade')
                    ->label('Grade with AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (OnlineExamAttempt $record): bool => AiAvailability::enabled()
                        && $record->onlineExam?->ai_grade_enabled
                        && in_array($record->status, ['submitted', 'graded'], true))
                    ->requiresConfirmation()
                    ->modalHeading('AI-grade short/essay answers?')
                    ->modalDescription('Auto-grading is allowed for exams. Marks are saved and remain editable.')
                    ->action(function (OnlineExamAttempt $record): void {
                        try {
                            $graded = app(ExamQuestionAiService::class)->gradePendingForAttempt($record);
                            Notification::make()
                                ->title("AI graded {$graded} answer(s)")
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('AI grading failed')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }
}
