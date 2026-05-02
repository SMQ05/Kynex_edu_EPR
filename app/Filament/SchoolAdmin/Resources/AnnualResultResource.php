<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\AnnualResultResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AnnualResult;
use App\Models\Tenant\SchoolClass;
use App\Services\AnnualResultService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AnnualResultResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'generate_report_cards';

    protected static ?string $model = AnnualResult::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Annual Results';

    protected static ?string $modelLabel = 'Annual Result';

    protected static ?string $pluralModelLabel = 'Annual Results';

    protected static string | \UnitEnum | null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        // View-only resource — no form.
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(['students.first_name', 'students.last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->sortable(),
                Tables\Columns\TextColumn::make('section.name')->label('Section')->placeholder('—'),
                Tables\Columns\TextColumn::make('academicYear.name')->label('Year')->sortable(),

                Tables\Columns\TextColumn::make('exam_score')->label('Exams')->numeric(2)->suffix('%'),
                Tables\Columns\TextColumn::make('homework_score')->label('HW')->numeric(2)->suffix('%')->toggleable(),
                Tables\Columns\TextColumn::make('class_assignment_score')->label('Assign')->numeric(2)->suffix('%')->toggleable(),

                Tables\Columns\TextColumn::make('final_percentage')
                    ->label('Final %')
                    ->numeric(2)
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state): string => ((float) $state) >= 33 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('grade')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'pass' => 'success',
                        'fail' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('generated_at')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->label('Academic Year')
                    ->relationship('academicYear', 'name')
                    ->default(fn () => AcademicYear::current()->value('id')),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pass' => 'Pass',
                        'fail' => 'Fail',
                    ]),
            ])
            ->headerActions([
                Action::make('compute')
                    ->label('Compute / Recompute')
                    ->icon('heroicon-o-calculator')
                    ->color('primary')
                    ->form([
                        Select::make('academic_year_id')
                            ->label('Academic Year')
                            ->options(fn () => AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                            ->required()
                            ->default(fn () => AcademicYear::current()->value('id')),

                        Select::make('class_id')
                            ->label('Limit to Class (optional)')
                            ->options(fn () => SchoolClass::orderBy('sort_order')->pluck('name', 'id'))
                            ->nullable(),
                    ])
                    ->action(function (array $data) {
                        $count = app(AnnualResultService::class)
                            ->computeForAcademicYear($data['academic_year_id'], $data['class_id'] ?? null);

                        Notification::make()
                            ->title('Annual results computed')
                            ->body("{$count} student record(s) updated.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('downloadPdf')
                    ->label('Result Card')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (AnnualResult $record) => route('result-card.pdf', ['result' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('rank');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnualResults::route('/'),
        ];
    }
}
