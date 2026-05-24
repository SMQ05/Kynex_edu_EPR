<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\LessonResource\RelationManagers;

use App\Models\Tenant\SyllabusTopic;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\LessonPlanAiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'lessonPlans';

    protected static ?string $title = 'Lesson Plans';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),

            DatePicker::make('plan_date')->label('Scheduled date'),
            TextInput::make('week_number')->label('Week #')->numeric()->minValue(1)->maxValue(60),
            TextInput::make('duration_minutes')->label('Duration (min)')->numeric()->minValue(5)->maxValue(600),
            Select::make('status')
                ->options([
                    'planned'     => 'Planned',
                    'in_progress' => 'In Progress',
                    'completed'   => 'Completed',
                ])
                ->default('planned')->required(),

            Select::make('syllabus_topic_id')
                ->label('Linked syllabus topic (optional)')
                ->options(fn (): array => SyllabusTopic::query()
                    ->orderByDesc('created_at')
                    ->limit(200)
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()->nullable()->columnSpanFull()
                ->helperText('Keeps lesson plans in sync with the existing syllabus coverage.'),

            Textarea::make('objectives')
                ->rows(3)->columnSpanFull()
                ->hintAction(static::aiGenerateAction()),
            Textarea::make('activities')->rows(3)->columnSpanFull(),
            Textarea::make('teaching_resources')->label('Teaching resources')->rows(2)->columnSpanFull(),
            Textarea::make('assessment')->rows(2)->columnSpanFull(),
            Textarea::make('homework')->rows(2)->columnSpanFull(),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ])->columns(3);
    }

    /**
     * 🤖 Generate the full lesson plan (objectives + activities + resources +
     * assessment + homework) from the lesson/topic title. Inserts into the
     * form for review before saving.
     */
    protected static function aiGenerateAction(): Action
    {
        return Action::make('aiGeneratePlan')
            ->label('Generate with AI')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->visible(fn (): bool => AiAvailability::enabled())
            ->action(function (Get $get, Set $set, RelationManager $livewire): void {
                $lesson = $livewire->getOwnerRecord();
                $topic  = trim((string) ($get('title') ?: $lesson?->title));

                if ($topic === '') {
                    Notification::make()->title('Enter a title first')->warning()->send();

                    return;
                }

                try {
                    $result = app(LessonPlanAiService::class)->generate($topic, [
                        'subject'          => $lesson?->subject?->name ?? '',
                        'class'            => $lesson?->schoolClass?->name ?? '',
                        'duration_minutes' => $get('duration_minutes'),
                    ]);

                    $set('objectives', $result['objectives']);
                    $set('activities', $result['activities']);
                    $set('teaching_resources', $result['teaching_resources']);
                    $set('assessment', $result['assessment']);
                    if (trim((string) $get('homework')) === '') {
                        $set('homework', $result['homework']);
                    }

                    Notification::make()->title('Lesson plan drafted — review before saving')->success()->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('AI generation failed')->body($e->getMessage())->danger()->send();
                }
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_date')->date('d M Y')->placeholder('—')->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('week_number')->label('Week')->placeholder('—')->toggleable(),
                TextColumn::make('duration_minutes')->label('Mins')->placeholder('—')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed'   => 'success',
                        'in_progress' => 'warning',
                        default       => 'gray',
                    }),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('plan_date', 'desc');
    }
}
