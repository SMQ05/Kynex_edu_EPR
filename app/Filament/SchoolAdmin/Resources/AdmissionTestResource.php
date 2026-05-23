<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\AdmissionTestResource\Pages;
use App\Filament\SchoolAdmin\Resources\AdmissionTestResource\RelationManagers;
use App\Models\Tenant\AdmissionTest;
use App\Services\AdmissionDelegationService;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;

class AdmissionTestResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'create_admission_tests';

    protected static ?string $model = AdmissionTest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Admission Tests';

    protected static string | \UnitEnum | null $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 3;

    /**
     * Class-scoped visibility: a delegated test author sees only the
     * tests for classes they own. NULL-class tests (year-wide) are
     * always visible to everyone with the permission.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth('school_users')->user();
        if (! $user) {
            return $query;
        }

        $allowed = app(AdmissionDelegationService::class)
            ->allowedClassIdsForUser($user, 'create_admission_tests');

        if ($allowed === null) {
            return $query;
        }
        if ($allowed === []) {
            return $query->whereRaw('1 = 0');
        }

        // Include year-wide (class_id IS NULL) tests too — they apply to
        // every class so a delegated author can use them as templates.
        return $query->where(function ($q) use ($allowed) {
            $q->whereNull('class_id')->orWhereIn('class_id', $allowed);
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Test Details')
                ->columns(2)
                ->schema([
                    Components\Select::make('academic_year_id')
                        ->relationship('academicYear', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Components\Select::make('class_id')
                        ->label('Class (leave blank for year-wide test)')
                        ->relationship('schoolClass', 'name')
                        ->searchable()
                        ->preload(),
                    Components\TextInput::make('name')->required()->maxLength(255),
                    Components\Toggle::make('is_active')->default(true),
                    Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                    Components\Textarea::make('instructions')
                        ->rows(4)
                        ->placeholder('Read each question carefully. You cannot return to a question once submitted.')
                        ->columnSpanFull(),
                ]),

            Section::make('Marking & Timing')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('duration_minutes')->numeric()->minValue(1)->default(60)->required()->suffix('min'),
                    Components\TextInput::make('total_marks')->numeric()->minValue(1)->default(100)->required(),
                    Components\TextInput::make('passing_marks')->numeric()->minValue(0)->default(40)->required(),
                    Components\Toggle::make('shuffle_questions')->default(true),
                    Components\Toggle::make('show_score_to_applicant')
                        ->helperText('Show the auto-graded score immediately after submit. Essay questions still require manual grading.')
                        ->default(false),
                ]),

            Section::make('Exam Scheduling')
                ->description('Set the exam date and the window during which students can start. Leave blank to allow access any time.')
                ->columns(3)
                ->schema([
                    Components\DatePicker::make('scheduled_date')
                        ->label('Exam Date')
                        ->helperText('Displayed to students on the intro page.'),
                    Components\DateTimePicker::make('window_opens_at')
                        ->label('Access Opens At')
                        ->helperText('Students cannot start before this time.'),
                    Components\DateTimePicker::make('window_closes_at')
                        ->label('Access Closes At')
                        ->helperText('No new starts allowed after this. Running exams auto-submit when their timer expires.')
                        ->after('window_opens_at'),
                ]),

            Section::make('Result Publication')
                ->description('Control when applicants can see their score.')
                ->columns(2)
                ->schema([
                    Components\Select::make('result_publication_mode')
                        ->label('Publication Mode')
                        ->options([
                            'auto'      => 'Automatic — show score as soon as grading is complete',
                            'manual'    => 'Manual — school publishes results on demand',
                            'scheduled' => 'Scheduled — results go live on a specific date/time',
                        ])
                        ->default('auto')
                        ->required()
                        ->live(),
                    Components\DateTimePicker::make('results_published_at')
                        ->label('Publish Results At')
                        ->helperText('For "Scheduled" mode: results become visible from this moment. For "Manual" mode: set this when you click Publish.')
                        ->visible(fn ($get) => in_array($get('result_publication_mode'), ['scheduled', 'manual'])),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('academicYear.name')->label('Year')->sortable(),
                Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->placeholder('All classes')->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Duration')->suffix(' min'),
                Tables\Columns\TextColumn::make('total_marks')->label('Total'),
                Tables\Columns\TextColumn::make('passing_marks')->label('Pass'),
                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions'),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Exam Date')
                    ->date('d M Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('result_publication_mode')
                    ->label('Results')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'auto'      => 'success',
                        'manual'    => 'warning',
                        'scheduled' => 'primary',
                        default     => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                EditAction::make(),
                TableAction::make('publish_results')
                    ->label('Publish Results')
                    ->icon('heroicon-o-megaphone')
                    ->color('success')
                    ->visible(fn (AdmissionTest $record) => $record->result_publication_mode === 'manual' && $record->results_published_at === null)
                    ->requiresConfirmation()
                    ->modalDescription('This will make all graded results visible to applicants immediately.')
                    ->action(function (AdmissionTest $record) {
                        $record->update(['results_published_at' => now()]);
                        Notification::make()->title('Results published')->success()->send();
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
            RelationManagers\AttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdmissionTests::route('/'),
            'create' => Pages\CreateAdmissionTest::route('/create'),
            'edit'   => Pages\EditAdmissionTest::route('/{record}/edit'),
        ];
    }
}
