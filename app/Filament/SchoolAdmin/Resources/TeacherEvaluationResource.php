<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\TeacherEvaluationResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\TeacherEvaluation;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiClassifier;
use App\Services\Ai\AiInsights;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeacherEvaluationResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_teacher_evaluations';

    protected static ?string $model = TeacherEvaluation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Staff & HR';

    protected static ?string $navigationLabel = 'Teacher Evaluations';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('Evaluation')
                ->columns(2)
                ->schema([
                    Select::make('staff_id')
                        ->label('Staff being evaluated')
                        ->options(fn (): array => static::staffOptions())
                        ->searchable()->required(),
                    Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(fn (): array => AcademicYear::orderByDesc('start_date')->pluck('name', 'id')->all())
                        ->searchable()->nullable(),
                    TextInput::make('period')->maxLength(255)->placeholder('e.g. Term 1 2026'),
                    DatePicker::make('evaluation_date')->default(now()),
                    Select::make('status')
                        ->options(TeacherEvaluation::STATUSES)
                        ->default('draft')->required()->native(false),
                ]),
            FormSection::make('Criteria & Scores')
                ->schema([
                    Repeater::make('criteria_scores')
                        ->label('Criteria')
                        ->schema([
                            TextInput::make('name')->label('Criterion')->required(),
                            TextInput::make('score')->numeric()->required()->minValue(0),
                            TextInput::make('max')->label('Max')->numeric()->required()->minValue(1)->default(10),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add criterion')
                        ->reorderable(false),
                ]),
            FormSection::make('Feedback')
                ->schema([
                    Textarea::make('strengths')->rows(2)->columnSpanFull(),
                    Textarea::make('improvements')->label('Areas to improve')->rows(2)->columnSpanFull(),
                    Textarea::make('comments')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    /** @return array<string,string> */
    protected static function staffOptions(): array
    {
        return StaffProfile::with('schoolUser')
            ->get()
            ->mapWithKeys(fn (StaffProfile $s): array => [
                $s->id => ($s->schoolUser?->name ?? 'Staff') . ($s->employee_id ? " ({$s->employee_id})" : ''),
            ])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff.schoolUser.name')->label('Staff')->searchable()->weight('semibold')->placeholder('—'),
                TextColumn::make('period')->placeholder('—')->toggleable(),
                TextColumn::make('evaluation_date')->date('d M Y')->sortable(),
                TextColumn::make('percentage')
                    ->label('Score')
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 1) . '%' : '—')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 75 => 'success',
                        (float) $state >= 50 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('sentiment')
                    ->badge()->placeholder('—')
                    ->color(fn (?string $state): string => match ($state) {
                        'positive' => 'success',
                        'negative' => 'danger',
                        'neutral'  => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TeacherEvaluation::STATUSES[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('status')->options(TeacherEvaluation::STATUSES),
                SelectFilter::make('staff_id')->label('Staff')->options(fn (): array => static::staffOptions())->searchable(),
            ])
            ->defaultSort('evaluation_date', 'desc')
            ->actions([
                Action::make('aiAnalyze')
                    ->label('AI Analyze')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->action(fn (TeacherEvaluation $record) => static::runAnalyze($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    /** 🤖 Sentiment of the feedback + a short AI summary of the evaluation. */
    protected static function runAnalyze(TeacherEvaluation $record): void
    {
        $feedback = trim(implode("\n", array_filter([
            $record->strengths,
            $record->improvements,
            $record->comments,
        ])));

        if ($feedback === '') {
            Notification::make()->title('No feedback to analyze')->warning()->send();

            return;
        }

        try {
            $sentiment = app(AiClassifier::class)->sentiment($feedback, 'teacher_eval_sentiment');

            $summary = app(AiInsights::class)->summarize(
                data: [
                    'staff'        => $record->staff?->schoolUser?->name,
                    'period'       => $record->period,
                    'percentage'   => $record->percentage,
                    'criteria'     => $record->criteria_scores,
                    'strengths'    => $record->strengths,
                    'improvements' => $record->improvements,
                    'comments'     => $record->comments,
                ],
                instruction: 'Summarise this teacher evaluation in a few sentences for an HR record — overall standing, '
                    . 'key strengths and the main development area. Be fair and specific.',
                feature: 'teacher_eval_summary',
            );

            $record->update([
                'sentiment'  => $sentiment['sentiment'],
                'ai_summary' => $summary,
            ]);

            Notification::make()->title('Analysis complete — sentiment: ' . $sentiment['sentiment'])->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI analyze failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeacherEvaluations::route('/'),
            'create' => Pages\CreateTeacherEvaluation::route('/create'),
            'edit'   => Pages\EditTeacherEvaluation::route('/{record}/edit'),
        ];
    }
}
