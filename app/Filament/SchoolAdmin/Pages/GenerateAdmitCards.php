<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\ExamSetting;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateAdmitCards extends Page implements HasForms, HasTable
{
    use HasPermissionCheck;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $rbacPermission = 'manage_exam_plan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Admit Cards';

    protected static ?string $title = 'Generate Admit Cards';

    protected string $view = 'filament.school-admin.pages.generate-admit-cards';

    public ?string $exam_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('exam_id')
                ->label('Exam')
                ->options(Exam::orderByDesc('created_at')->pluck('name', 'id'))
                ->required()
                ->reactive(),

            Select::make('class_id')
                ->label('Class')
                ->options(SchoolClass::orderBy('sort_order')->pluck('name', 'id'))
                ->reactive()
                ->afterStateUpdated(fn () => $this->section_id = null),

            Select::make('section_id')
                ->label('Section')
                ->options(fn () => $this->class_id
                    ? Section::where('class_id', $this->class_id)->pluck('name', 'id')
                    : [])
                ->reactive(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::query()
                    ->when($this->class_id, fn ($q) => $q->where('class_id', $this->class_id))
                    ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
                    ->where('status', 'enrolled')
            )
            ->columns([
                TextColumn::make('roll_number')->label('Roll')->sortable(),
                TextColumn::make('admission_number')->sortable()->searchable(),
                TextColumn::make('full_name')->label('Student')->searchable(['first_name', 'last_name']),
                TextColumn::make('schoolClass.name')->label('Class'),
                TextColumn::make('section.name')->label('Section'),
            ])
            ->bulkActions([
                BulkAction::make('generate_admit_cards')
                    ->label('Generate & Download')
                    ->icon('heroicon-o-ticket')
                    ->requiresConfirmation()
                    ->modalHeading('Generate admit cards')
                    ->modalDescription('A printable admit-card PDF will be produced for the selected students.')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => $this->generate($records)),
            ]);
    }

    protected function generate(Collection $records): ?StreamedResponse
    {
        if (! $this->exam_id) {
            Notification::make()->title('Please select an exam first')->danger()->send();

            return null;
        }
        if ($records->isEmpty()) {
            Notification::make()->title('No students selected')->warning()->send();

            return null;
        }

        $exam = Exam::with('academicYear')->find($this->exam_id);
        if (! $exam) {
            Notification::make()->title('Exam not found')->danger()->send();

            return null;
        }

        $students = $records->load(['schoolClass', 'section']);

        // Pre-load schedules per class for the exam (subjects + times + room).
        $classIds = $students->pluck('class_id')->unique()->filter()->all();
        $schedulesByClass = ExamSchedule::with('subject')
            ->where('exam_id', $exam->id)
            ->whereIn('class_id', $classIds)
            ->orderBy('exam_date')
            ->get()
            ->groupBy('class_id');

        // Attach a renderable photo data-uri to each student.
        $students->each(function (Student $s): void {
            $s->setAttribute('photo_data_uri', $this->photoDataUri($s));
        });

        $settings = (array) (ExamSetting::get('admit_card', []));

        $pdf = Pdf::loadView('pdf.admit-card', [
            'exam'             => $exam,
            'students'         => $students,
            'schedulesByClass' => $schedulesByClass,
            'schoolName'       => tenant()?->school_name ?? config('app.name'),
            'settings'         => $settings,
        ])->setPaper('a4');

        $filename = 'admit-cards-' . now()->format('Ymd-His') . '.pdf';
        $bytes = $pdf->output();

        Notification::make()
            ->title("Generated admit cards for {$students->count()} student(s)")
            ->success()
            ->send();

        return response()->streamDownload(
            fn () => print($bytes),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /** Resolve a student's photo into a base64 data URI for DomPDF. */
    protected function photoDataUri(Student $student): ?string
    {
        $path = $student->profile_photo_path ?? null;
        if (! $path) {
            return null;
        }

        try {
            $disk = Storage::disk('tenant');
            if (! $disk->exists($path)) {
                return null;
            }
            $contents = $disk->get($path);
            $mime = $disk->mimeType($path) ?: 'image/jpeg';

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (\Throwable) {
            return null;
        }
    }
}
