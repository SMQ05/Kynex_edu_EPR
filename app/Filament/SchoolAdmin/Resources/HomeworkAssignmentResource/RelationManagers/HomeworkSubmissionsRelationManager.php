<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\HomeworkAssignmentResource\RelationManagers;

use App\Jobs\NotifyHomeworkGraded;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
                Action::make('grade')
                    ->label('Grade')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form([
                        TextInput::make('grade')
                            ->label('Grade / Marks')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('e.g. A+, 85, 9/10'),

                        Textarea::make('feedback')
                            ->label('Feedback')
                            ->rows(3)
                            ->placeholder('Optional feedback for the student'),
                    ])
                    ->fillForm(fn ($record) => [
                        'grade'    => $record->grade,
                        'feedback' => $record->feedback,
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'grade'     => $data['grade'],
                            'feedback'  => $data['feedback'] ?? null,
                            'graded_by' => auth()->id(),
                            'graded_at' => now(),
                        ]);

                        NotifyHomeworkGraded::dispatch($record->id);
                    }),

                Tables\Actions\ViewAction::make()
                    ->form([
                        TextInput::make('submission_text')->disabled(),
                        TextInput::make('grade')->disabled(),
                        Textarea::make('feedback')->disabled(),
                    ]),
            ]);
    }
}
