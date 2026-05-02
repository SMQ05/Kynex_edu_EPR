<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Resources\AcademicYearResource\Pages;
use App\Models\Tenant\AcademicYear;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class AcademicYearResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_academic_calendar';

    protected static ?string $model = AcademicYear::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Academic Years';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 2;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Academic Year Details')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. 2025-2026'),

                    Components\Toggle::make('is_current')
                        ->label('Current Academic Year')
                        ->helperText('Only one year can be current at a time.')
                        ->default(false),

                    Components\DatePicker::make('start_date')
                        ->required()
                        ,

                    Components\DatePicker::make('end_date')
                        ->required()

                        ->afterOrEqual('start_date'),
                ]),

            Section::make('Annual Result Weights')
                ->description('Component weights for the annual result. The three values must sum to 100.')
                ->columns(3)
                ->schema([
                    Components\TextInput::make('exam_weight_percent')
                        ->label('Exams')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(80)
                        ->required(),

                    Components\TextInput::make('homework_weight_percent')
                        ->label('Homework')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(10)
                        ->required(),

                    Components\TextInput::make('class_assignment_weight_percent')
                        ->label('Class Assignments')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(10)
                        ->required(),
                ])
                ->collapsed(false),
        ]);
    }

    /**
     * Validate that the three weight fields sum to 100. Called from the
     * Create/Edit page's mutateFormDataBeforeSave hook.
     */
    public static function ensureWeightsSumTo100(array $data): array
    {
        $sum = (int) ($data['exam_weight_percent'] ?? 0)
            + (int) ($data['homework_weight_percent'] ?? 0)
            + (int) ($data['class_assignment_weight_percent'] ?? 0);

        if ($sum !== 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.exam_weight_percent' => "Weights must sum to 100% (got {$sum}%).",
            ]);
        }

        return $data;
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_current')
                    ->label('Current')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Students')
                    ->counts('students')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_current')
                    ->label('Current Year'),
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

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
}
