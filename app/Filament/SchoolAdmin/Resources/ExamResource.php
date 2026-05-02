<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Models\Tenant\Exam;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ExamResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_marks';
    protected static string $rbacWritePermission = 'manage_exam_schedules';

    protected static ?string $model = Exam::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Exam Details')->schema([
                Select::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. First Term Exam, Mid Term'),

                Select::make('exam_type')
                    ->label('Exam Type')
                    ->options(ExamType::options())
                    ->placeholder('Select type')
                    ->helperText('Used for weighted annual result calculation.'),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('start_date'),

                DatePicker::make('end_date')
                    ->afterOrEqual('start_date'),

                Select::make('status')
                    ->options(
                        collect(ExamStatus::cases())
                            ->mapWithKeys(fn (ExamStatus $s) => [$s->value => $s->label()])
                            ->all()
                    )
                    ->default(ExamStatus::Draft->value)
                    ->required(),

                Toggle::make('publish_results')
                    ->label('Publish Results')
                    ->helperText('Make results visible to students and parents'),
            ])->columns(2),

            // ── Part 5d: Weightage section ─────────────────────────────
            Section::make('Annual Result Weightage')
                ->description('Configure how this exam contributes to the annual result calculation.')
                ->collapsed()
                ->schema([
                    TextInput::make('weightage_percent')
                        ->label('Weightage (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(100)
                        ->suffix('%')
                        ->helperText('Percentage weight of this exam in the annual result (e.g. 30 for a 30% weighted term)'),

                    TextInput::make('weightage_label')
                        ->label('Weightage Label')
                        ->maxLength(100)
                        ->nullable()
                        ->placeholder('e.g. Term 1 (30%), Mid-Year, Finals')
                        ->helperText('Optional display label shown on report cards'),

                    Toggle::make('include_in_annual_result')
                        ->label('Include in Annual Result')
                        ->default(true)
                        ->helperText('If disabled, this exam is excluded from annual result calculations entirely'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Academic Year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('exam_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ExamType ? $state->label() : (ExamType::tryFrom((string) $state)?->label() ?? '—'))
                    ->color(fn ($state): string => ($state instanceof ExamType ? $state : ExamType::tryFrom((string) $state))?->color() ?? 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ExamStatus ? $state->label() : (ExamStatus::tryFrom((string) $state)?->label() ?? $state))
                    ->color(fn ($state): string => ($state instanceof ExamStatus ? $state : ExamStatus::tryFrom((string) $state))?->color() ?? 'gray'),

                Tables\Columns\IconColumn::make('publish_results')
                    ->boolean()
                    ->label('Published'),

                Tables\Columns\TextColumn::make('schedules_count')
                    ->counts('schedules')
                    ->label('Subjects'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        collect(ExamStatus::cases())
                            ->mapWithKeys(fn (ExamStatus $s) => [$s->value => $s->label()])
                            ->all()
                    ),

                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->label('Academic Year'),

                Tables\Filters\SelectFilter::make('exam_type')
                    ->label('Type')
                    ->options(ExamType::options()),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ExamResource\Pages\ListExams::route('/'),
            'create' => ExamResource\Pages\CreateExam::route('/create'),
            'edit'   => ExamResource\Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
