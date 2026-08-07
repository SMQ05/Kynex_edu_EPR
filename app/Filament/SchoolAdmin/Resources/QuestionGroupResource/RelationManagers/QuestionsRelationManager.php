<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\QuestionGroupResource\RelationManagers;

use App\Services\Ai\AiAvailability;
use App\Services\Ai\ExamQuestionAiService;
use App\Services\DocumentExtractor;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Questions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->options([
                    'mcq'          => 'Multiple choice',
                    'true_false'   => 'True / False',
                    'short_answer' => 'Short answer',
                    'essay'        => 'Essay (AI / manual grading)',
                    'math'         => 'Math',
                ])
                ->default('mcq')
                ->required()
                ->live(),

            Select::make('difficulty')
                ->options([
                    'easy'   => 'Easy',
                    'medium' => 'Medium',
                    'hard'   => 'Hard',
                ])
                ->default('medium')
                ->required(),

            TextInput::make('marks')
                ->numeric()->step('0.5')->default(1)->required(),

            Textarea::make('question_text')
                ->required()->rows(3)->columnSpanFull(),

            Repeater::make('options')
                ->label('Answer choices')
                ->helperText('Type each option on its own line. The system labels them A, B, C, … automatically.')
                ->schema([
                    TextInput::make('text')
                        ->placeholder('Option text')
                        ->required(),
                ])
                ->minItems(2)
                ->defaultItems(4)
                ->reorderable(true)
                ->live()
                ->visible(fn (callable $get) => $get('type') === 'mcq')
                ->afterStateHydrated(function (Repeater $component, $state) {
                    if (is_array($state) && $state !== [] && ! array_is_list($state)) {
                        $firstValue = reset($state);
                        if (is_array($firstValue)) {
                            return;
                        }

                        $component->state(
                            array_map(fn ($v) => ['text' => (string) $v], array_values($state))
                        );
                    }
                })
                ->dehydrateStateUsing(function (?array $state) {
                    if (! is_array($state) || $state === []) {
                        return null;
                    }
                    $keyed = [];
                    $i = 0;
                    foreach ($state as $row) {
                        $text = trim((string) ($row['text'] ?? ''));
                        if ($text === '') {
                            continue;
                        }
                        $keyed[chr(65 + $i)] = $text;
                        $i++;
                    }

                    return $keyed ?: null;
                })
                ->columnSpanFull(),

            Select::make('correct_answer')
                ->label('Correct answer')
                ->options(function (callable $get): array {
                    $type = $get('type');
                    if ($type === 'true_false') {
                        return ['true' => 'True', 'false' => 'False'];
                    }
                    if ($type === 'mcq') {
                        $opts = $get('options') ?? [];
                        $result = [];
                        $i = 0;
                        foreach ($opts as $row) {
                            $text = is_array($row) ? ($row['text'] ?? '') : (string) $row;
                            if (trim((string) $text) === '') {
                                continue;
                            }
                            $key = chr(65 + $i);
                            $result[$key] = "{$key}. {$text}";
                            $i++;
                        }

                        return $result;
                    }

                    return [];
                })
                ->visible(fn (callable $get) => in_array($get('type'), ['mcq', 'true_false'], true))
                ->required(fn (callable $get) => in_array($get('type'), ['mcq', 'true_false'], true))
                ->columnSpanFull(),

            TextInput::make('correct_answer_text')
                ->label('Reference answer (optional — leave blank for AI / manual grading)')
                ->placeholder('e.g. 42  or  Mitochondria')
                ->visible(fn (callable $get) => in_array($get('type'), ['short_answer', 'math'], true))
                ->dehydrated(false)
                ->afterStateHydrated(function (TextInput $component, $state, $record) {
                    if ($record && in_array($record->type, ['short_answer', 'math'], true)) {
                        $component->state($record->correct_answer);
                    }
                })
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if (in_array($get('type'), ['short_answer', 'math'], true)) {
                        $set('correct_answer', $state);
                    }
                })
                ->columnSpanFull(),

            Textarea::make('explanation')
                ->label('Explanation (optional)')
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('difficulty')->badge()->color(fn (string $state): string => match ($state) {
                    'easy'   => 'success',
                    'hard'   => 'danger',
                    default  => 'warning',
                }),
                TextColumn::make('question_text')->limit(80)->searchable()->wrap(),
                TextColumn::make('marks')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'mcq'          => 'Multiple choice',
                    'true_false'   => 'True / False',
                    'short_answer' => 'Short answer',
                    'essay'        => 'Essay',
                    'math'         => 'Math',
                ]),
                SelectFilter::make('difficulty')->options([
                    'easy'   => 'Easy',
                    'medium' => 'Medium',
                    'hard'   => 'Hard',
                ]),
            ])
            ->headerActions([
                CreateAction::make(),

                Action::make('aiGenerate')
                    ->label('Generate with AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->modalHeading('Generate questions with AI')
                    ->modalDescription('Provide source material — upload documents, paste URLs, type notes, or any combination. Generated questions are added to this group for review.')
                    ->modalWidth('3xl')
                    ->form([
                        FileUpload::make('files')
                            ->label('Upload documents')
                            ->helperText('PDF, DOCX, TXT, or MD. Multiple files welcome.')
                            ->multiple()
                            ->disk('local')
                            ->directory('exam-ai-uploads')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'text/plain',
                                'text/markdown',
                                'text/csv',
                            ])
                            ->maxSize(20480)
                            ->reorderable()
                            ->columnSpanFull(),

                        TagsInput::make('urls')
                            ->label('Add URLs')
                            ->helperText('Press Enter after each URL.')
                            ->placeholder('https://en.wikipedia.org/wiki/Photosynthesis')
                            ->columnSpanFull(),

                        Textarea::make('source')
                            ->label('Or paste / type notes')
                            ->placeholder('Optional. A topic outline, learning objectives, or free-form context.')
                            ->rows(5)
                            ->columnSpanFull(),

                        TextInput::make('mcq_count')->label('# MCQ')->numeric()->minValue(0)->maxValue(50)->default(5),
                        TextInput::make('tf_count')->label('# True / False')->numeric()->minValue(0)->maxValue(50)->default(0),
                        TextInput::make('short_count')->label('# Short answer')->numeric()->minValue(0)->maxValue(50)->default(0),
                        TextInput::make('essay_count')->label('# Essay')->numeric()->minValue(0)->maxValue(20)->default(0),
                        TextInput::make('math_count')->label('# Math')->numeric()->minValue(0)->maxValue(50)->default(0),
                        Select::make('difficulty')
                            ->options([
                                'easy'   => 'Easy',
                                'medium' => 'Medium',
                                'hard'   => 'Hard',
                            ])
                            ->default('medium'),
                    ])
                    ->action(function (array $data) {
                        $group = $this->getOwnerRecord();
                        if (! $group) {
                            return;
                        }

                        $extractor    = app(DocumentExtractor::class);
                        $sourceParts  = [];
                        $cleanupPaths = [];
                        $errors       = [];

                        foreach ((array) ($data['files'] ?? []) as $stored) {
                            $relative = is_string($stored) ? $stored : (string) $stored;
                            $disk = Storage::disk('local');
                            if (! $disk->exists($relative)) {
                                continue;
                            }
                            $absolute = $disk->path($relative);
                            $cleanupPaths[] = $relative;
                            try {
                                $text = $extractor->fromPath(path: $absolute, originalName: basename($relative));
                                $sourceParts[] = '## File: ' . basename($relative) . "\n\n" . $text;
                            } catch (\Throwable $e) {
                                $errors[] = basename($relative) . ': ' . $e->getMessage();
                            }
                        }

                        foreach ((array) ($data['urls'] ?? []) as $url) {
                            $url = trim((string) $url);
                            if ($url === '') {
                                continue;
                            }
                            try {
                                $text = $extractor->fromUrl($url);
                                $sourceParts[] = "## URL: {$url}\n\n" . $text;
                            } catch (\Throwable $e) {
                                $errors[] = $url . ': ' . $e->getMessage();
                            }
                        }

                        $notes = trim((string) ($data['source'] ?? ''));
                        if ($notes !== '') {
                            $sourceParts[] = "## Additional notes\n\n" . $notes;
                        }

                        if ($sourceParts === []) {
                            foreach ($cleanupPaths as $rel) {
                                Storage::disk('local')->delete($rel);
                            }
                            Notification::make()
                                ->title('No usable source content')
                                ->body($errors !== [] ? implode("\n", $errors) : 'Add at least one file, URL, or some notes.')
                                ->danger()->send();

                            return;
                        }

                        $combined = implode("\n\n---\n\n", $sourceParts);
                        if (mb_strlen($combined) > 90_000) {
                            $combined = mb_substr($combined, 0, 90_000) . "\n\n[...combined source truncated]";
                        }

                        try {
                            $created = app(ExamQuestionAiService::class)->generateQuestions(
                                group: $group,
                                source: $combined,
                                counts: [
                                    'mcq'          => (int) ($data['mcq_count']   ?? 0),
                                    'true_false'   => (int) ($data['tf_count']    ?? 0),
                                    'short_answer' => (int) ($data['short_count'] ?? 0),
                                    'essay'        => (int) ($data['essay_count'] ?? 0),
                                    'math'         => (int) ($data['math_count']  ?? 0),
                                ],
                                difficulty: $data['difficulty'] ?? 'medium',
                            );

                            $body = 'Review them in the list and edit if needed.';
                            if ($errors !== []) {
                                $body .= "\n\nWarnings:\n" . implode("\n", $errors);
                            }

                            Notification::make()
                                ->title("Generated {$created} question(s)")
                                ->body($body)
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Log::warning('Exam AI question generation failed', ['error' => $e->getMessage()]);
                            Notification::make()
                                ->title('AI generation failed')
                                ->body($e->getMessage())
                                ->danger()->send();
                        } finally {
                            foreach ($cleanupPaths as $rel) {
                                try {
                                    Storage::disk('local')->delete($rel);
                                } catch (\Throwable) {
                                }
                            }
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
