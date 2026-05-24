<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\RelationManagers;

use App\Filament\SchoolAdmin\Support\CheckOriginalityAction;
use App\Jobs\NotifyHomeworkGraded;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeworkSubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Submissions';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => ($record->student?->first_name ?? '') . ' ' . ($record->student?->last_name ?? ''))
                    ->searchable(['student.first_name', 'student.last_name'])
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('attachment_path')
                    ->label('Attachment')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('marks_obtained')
                    ->label('Marks')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === null) {
                            return null;
                        }
                        return rtrim(rtrim((string) $state, '0'), '.')
                            . ($record->total_marks ? ' / ' . $record->total_marks : '');
                    })
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('grade')
                    ->label('Grade')
                    ->default('Not graded')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning'),

                TextColumn::make('feedback')
                    ->label('Feedback')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('gradedBy.name')
                    ->label('Graded By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('graded_at')
                    ->label('Graded At')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->actions([
                // Premium, opt-in, advisory-only AI-originality check (auto-hidden
                // unless the school has enabled Originality.ai). Never grades.
                CheckOriginalityAction::make('submission_text'),
                Action::make('grade')
                    ->label('Grade')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form([
                        TextInput::make('marks_obtained')
                            ->label('Marks Obtained')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->helperText('Numeric score. Used in the weighted annual result.'),

                        TextInput::make('total_marks')
                            ->label('Out of (Total Marks)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Defaults to the assignment total marks if blank.'),

                        TextInput::make('grade')
                            ->label('Letter Grade (optional)')
                            ->maxLength(20)
                            ->placeholder('e.g. A+, B, etc.'),

                        Textarea::make('feedback')
                            ->label('Feedback')
                            ->rows(3)
                            ->placeholder('Optional feedback for the student'),
                    ])
                    ->fillForm(fn ($record) => [
                        'marks_obtained' => $record->marks_obtained,
                        'total_marks'    => $record->total_marks ?? $record->homework?->total_marks,
                        'grade'          => $record->grade,
                        'feedback'       => $record->feedback,
                    ])
                    ->action(function ($record, array $data): void {
                        // Inherit assignment total_marks if user left it blank.
                        $totalMarks = $data['total_marks']
                            ?? $record->homework?->total_marks;

                        $record->update([
                            'marks_obtained' => $data['marks_obtained'] ?? null,
                            'total_marks'    => $totalMarks,
                            'grade'          => $data['grade'] ?? null,
                            'feedback'       => $data['feedback'] ?? null,
                            'graded_by'      => auth()->guard('school_users')->id(),
                            'graded_at'      => now(),
                        ]);

                        NotifyHomeworkGraded::dispatch($record->id);
                    }),

                ViewAction::make()
                    ->form([
                        TextInput::make('submission_text')->disabled(),
                        TextInput::make('grade')->disabled(),
                        Textarea::make('feedback')->disabled(),
                    ]),
            ]);
    }
}
