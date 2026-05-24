<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\ExamAttendanceResource\Pages;
use App\Models\Tenant\ExamAttendanceRecord;
use App\Models\Tenant\ExamSchedule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExamAttendanceResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_exam_attendance';

    protected static ?string $model = ExamAttendanceRecord::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?string $navigationLabel = 'Exam Attendance';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'exam attendance record';

    /** Human label for an exam schedule option. */
    protected static function scheduleLabel(ExamSchedule $s): string
    {
        $s->loadMissing(['exam', 'schoolClass', 'section', 'subject']);

        return ($s->exam?->name ?? 'Exam')
            . ' · ' . ($s->schoolClass?->name ?? 'Class')
            . ($s->section ? ' (' . $s->section->name . ')' : '')
            . ' · ' . ($s->subject?->name ?? 'Subject')
            . ' · ' . optional($s->exam_date)->format('d M Y');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Exam Attendance')->schema([
                Select::make('exam_schedule_id')
                    ->label('Exam Schedule')
                    ->options(
                        fn () => ExamSchedule::with(['exam', 'schoolClass', 'section', 'subject'])
                            ->orderByDesc('exam_date')
                            ->get()
                            ->mapWithKeys(fn (ExamSchedule $s) => [$s->id => static::scheduleLabel($s)])
                            ->all()
                    )
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('student_id', null)),

                Select::make('student_id')
                    ->label('Student')
                    ->options(function (callable $get): array {
                        $scheduleId = $get('exam_schedule_id');
                        if (! $scheduleId) {
                            return [];
                        }
                        $schedule = ExamSchedule::find($scheduleId);
                        if (! $schedule) {
                            return [];
                        }

                        return \App\Models\Tenant\Student::query()
                            ->where('class_id', $schedule->class_id)
                            ->when($schedule->section_id, fn ($q) => $q->where('section_id', $schedule->section_id))
                            ->where('status', 'enrolled')
                            ->orderBy('roll_number')
                            ->get()
                            ->mapWithKeys(fn ($st) => [
                                $st->id => trim(($st->roll_number ? $st->roll_number . ' — ' : '') . $st->full_name),
                            ])
                            ->all();
                    })
                    ->searchable()
                    ->required(),

                Select::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent'  => 'Absent',
                        'late'    => 'Late',
                        'leave'   => 'Leave',
                    ])
                    ->default('present')
                    ->required(),

                Textarea::make('remarks')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('examSchedule.exam.name')->label('Exam')->sortable()->searchable(),
                TextColumn::make('examSchedule.subject.name')->label('Subject')->toggleable(),
                TextColumn::make('examSchedule.exam_date')->label('Date')->date()->sortable(),
                TextColumn::make('student.roll_number')->label('Roll')->toggleable(),
                TextColumn::make('student.full_name')->label('Student')->searchable(['first_name', 'last_name']),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent'  => 'danger',
                        'late'    => 'warning',
                        default   => 'gray',
                    }),
                TextColumn::make('remarks')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'present' => 'Present',
                    'absent'  => 'Absent',
                    'late'    => 'Late',
                    'leave'   => 'Leave',
                ]),
                SelectFilter::make('exam_schedule_id')
                    ->label('Exam Schedule')
                    ->options(
                        fn () => ExamSchedule::with(['exam', 'schoolClass', 'subject'])
                            ->orderByDesc('exam_date')
                            ->get()
                            ->mapWithKeys(fn (ExamSchedule $s) => [$s->id => static::scheduleLabel($s)])
                            ->all()
                    )
                    ->searchable(),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExamAttendanceRecords::route('/'),
            'create' => Pages\CreateExamAttendanceRecord::route('/create'),
            'edit'   => Pages\EditExamAttendanceRecord::route('/{record}/edit'),
        ];
    }
}
