<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\AdmissionCriteriaResource\Pages;
use App\Models\Tenant\AdmissionCriteria;
use App\Models\Tenant\SchoolClass;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class AdmissionCriteriaResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_student_admissions';

    protected static ?string $model = AdmissionCriteria::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Admission Criteria';

    protected static string | \UnitEnum | null $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Admission Criteria';

    protected static ?string $pluralModelLabel = 'Admission Criteria';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Scope')
                ->columns(2)
                ->schema([
                    Components\Select::make('academic_year_id')
                        ->relationship('academicYear', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Components\TextInput::make('name')
                        ->placeholder('e.g. Class 1 admission criteria — 2026'),

                    Components\Toggle::make('applies_to_all_classes')
                        ->label('Applies to whole school (all classes)')
                        ->helperText('When on, this criteria is the year-wide default for any class without its own criteria.')
                        ->default(false)
                        ->live()
                        ->columnSpanFull(),

                    Components\Select::make('classes')
                        ->label('Classes (pick one or many)')
                        ->relationship('classes', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->visible(fn (callable $get) => ! $get('applies_to_all_classes'))
                        ->required(fn (callable $get) => ! $get('applies_to_all_classes'))
                        ->helperText('Choose a single class or multiple classes that share the same criteria.')
                        ->columnSpanFull(),

                    Components\Toggle::make('is_active')->default(true),
                ]),

            Section::make('Component weightages')
                ->description('Weights must sum to 100. Set a component to 0 if you do not use it.')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('test_weightage')
                        ->label('Entry test')
                        ->numeric()->minValue(0)->maxValue(100)->suffix('%')
                        ->default(50)->required(),
                    Components\TextInput::make('interview_weightage')
                        ->label('Interview')
                        ->numeric()->minValue(0)->maxValue(100)->suffix('%')
                        ->default(30)->required(),
                    Components\TextInput::make('previous_score_weightage')
                        ->label('Previous record')
                        ->numeric()->minValue(0)->maxValue(100)->suffix('%')
                        ->default(20)->required(),
                ]),

            Section::make('Full marks')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('test_full_marks')
                        ->label('Entry test full marks')
                        ->numeric()->minValue(1)->default(100)->required(),
                    Components\TextInput::make('interview_full_marks')
                        ->label('Interview full marks')
                        ->numeric()->minValue(1)->default(100)->required(),
                ]),

            Section::make('Selection thresholds')
                ->description('Applicants below these minima can be auto-rejected before reaching the institute head.')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('min_test_score')
                        ->label('Minimum entry test score')
                        ->numeric()->step('0.01')
                        ->helperText('Applicants below this score will be auto-rejected if the toggle below is on.'),
                    Components\Toggle::make('auto_reject_below_test')
                        ->label('Auto-reject below test minimum')
                        ->default(true),

                    Components\TextInput::make('min_interview_score')
                        ->label('Minimum interview score')
                        ->numeric()->step('0.01'),
                    Components\Toggle::make('auto_reject_below_interview')
                        ->label('Auto-reject below interview minimum')
                        ->default(true),

                    Components\Toggle::make('skip_interview_for_all')
                        ->label('Skip interview for all applicants')
                        ->helperText('When ON, no applicant under this criteria will be sent through interview scheduling. The interview weightage is redistributed across the test and previous-school components when calculating the final percentage.')
                        ->default(false)
                        ->columnSpanFull(),

                    Components\TextInput::make('min_final_percentage')
                        ->label('Minimum final percentage for selection')
                        ->numeric()->step('0.01')->suffix('%')
                        ->helperText('Applicants whose weighted final percentage is below this are auto-rejected.'),
                ]),
        ])
        ->columns(1);
    }

    /**
     * Validate weightages sum to exactly 100.
     */
    public static function assertWeightagesSumTo100(array $data): void
    {
        $sum = (int) ($data['test_weightage'] ?? 0)
             + (int) ($data['interview_weightage'] ?? 0)
             + (int) ($data['previous_score_weightage'] ?? 0);

        if ($sum !== 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'test_weightage' => "Weightages must sum to exactly 100 (got {$sum}).",
            ]);
        }
    }

    /**
     * Validate scope rules:
     *   - If applies_to_all_classes is FALSE, at least one class must be selected.
     *   - Within an academic year, no class may belong to more than one criteria.
     *   - At most one applies_to_all_classes=true criteria per academic year.
     *
     * $excludeId lets the edit page skip the row being edited from the
     * conflict scan.
     */
    public static function assertScopeIsValid(array $data, ?string $excludeId = null): void
    {
        $appliesToAll = (bool) ($data['applies_to_all_classes'] ?? false);
        $classes = (array) ($data['classes'] ?? []);
        $yearId = $data['academic_year_id'] ?? null;

        if (! $appliesToAll && empty($classes)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'classes' => 'Pick at least one class, or turn on "Applies to whole school".',
            ]);
        }

        if (! $yearId) {
            return; // form-level required will catch this
        }

        if ($appliesToAll) {
            $exists = AdmissionCriteria::query()
                ->where('academic_year_id', $yearId)
                ->where('applies_to_all_classes', true)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();
            if ($exists) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'applies_to_all_classes' => 'Another whole-school criteria already exists for this academic year.',
                ]);
            }
            return;
        }

        // Per-class conflict: a class can only be claimed by one criteria
        // within an academic year.
        $conflict = DB::table('admission_criteria_class as p')
            ->join('admission_criteria as c', 'c.id', '=', 'p.admission_criteria_id')
            ->where('c.academic_year_id', $yearId)
            ->whereIn('p.class_id', $classes)
            ->when($excludeId, fn ($q) => $q->where('c.id', '!=', $excludeId))
            ->select('p.class_id', 'c.name as criteria_name')
            ->first();

        if ($conflict) {
            $className = SchoolClass::whereKey($conflict->class_id)->value('name') ?? $conflict->class_id;
            throw \Illuminate\Validation\ValidationException::withMessages([
                'classes' => "Class \"{$className}\" already belongs to another admission criteria"
                    . ($conflict->criteria_name ? " (\"{$conflict->criteria_name}\")" : '')
                    . ' for this academic year.',
            ]);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicYear.name')->label('Year')->sortable(),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Applies to')
                    ->state(function ($record) {
                        if ($record->applies_to_all_classes) {
                            return 'Whole school';
                        }
                        $names = $record->classes->pluck('name')->all();
                        return $names ? implode(', ', $names) : '—';
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('test_weightage')->label('Test %')->sortable(),
                Tables\Columns\TextColumn::make('interview_weightage')->label('Interview %')->sortable(),
                Tables\Columns\TextColumn::make('previous_score_weightage')->label('Prev %')->sortable(),
                Tables\Columns\TextColumn::make('min_test_score')->label('Min test')->placeholder('—'),
                Tables\Columns\TextColumn::make('min_interview_score')->label('Min interview')->placeholder('—'),
                Tables\Columns\TextColumn::make('min_final_percentage')->label('Min final %')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdmissionCriteria::route('/'),
            'create' => Pages\CreateAdmissionCriteria::route('/create'),
            'edit'   => Pages\EditAdmissionCriteria::route('/{record}/edit'),
        ];
    }
}
