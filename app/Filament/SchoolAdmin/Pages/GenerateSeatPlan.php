<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamSeatPlan;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Models\Tenant\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateSeatPlan extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_exam_plan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Seat Plan';

    protected static ?string $title = 'Generate Seat Plan';

    protected string $view = 'filament.school-admin.pages.generate-seat-plan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'rooms'        => [['room' => 'Room 1', 'capacity' => 30]],
            'seats_per_row' => 5,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('exam_id')
                    ->label('Exam')
                    ->options(Exam::orderByDesc('created_at')->pluck('name', 'id'))
                    ->required()
                    ->reactive(),

                Select::make('class_id')
                    ->label('Class (optional — leave blank for all classes)')
                    ->options(SchoolClass::orderBy('sort_order')->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('section_id', null)),

                Select::make('section_id')
                    ->label('Section (optional)')
                    ->options(fn (callable $get) => $get('class_id')
                        ? Section::where('class_id', $get('class_id'))->pluck('name', 'id')
                        : [])
                    ->reactive(),

                Repeater::make('rooms')
                    ->label('Rooms')
                    ->schema([
                        TextInput::make('room')->label('Room name')->required(),
                        TextInput::make('capacity')->label('Capacity')->numeric()->minValue(1)->default(30)->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('Add room')
                    ->columnSpanFull(),

                TextInput::make('seats_per_row')
                    ->label('Seats per row')
                    ->numeric()->minValue(1)->default(5)
                    ->helperText('Used to compute row/column for each seat.'),

                Toggle::make('shuffle')
                    ->label('Shuffle students before allocating')
                    ->helperText('Mix students so neighbours are less predictable.')
                    ->default(false),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $state = $this->form->getState();

        $examId = $state['exam_id'] ?? null;
        if (! $examId) {
            Notification::make()->title('Please select an exam')->danger()->send();

            return;
        }

        $students = Student::query()
            ->when($state['class_id'] ?? null, fn ($q) => $q->where('class_id', $state['class_id']))
            ->when($state['section_id'] ?? null, fn ($q) => $q->where('section_id', $state['section_id']))
            ->where('status', 'enrolled')
            ->orderBy('class_id')
            ->orderBy('roll_number')
            ->get();

        if ($students->isEmpty()) {
            Notification::make()->title('No enrolled students match the filters')->warning()->send();

            return;
        }

        if ($state['shuffle'] ?? false) {
            $students = $students->shuffle();
        }

        $rooms = collect($state['rooms'] ?? [])
            ->map(fn ($r) => ['room' => trim((string) ($r['room'] ?? '')), 'capacity' => (int) ($r['capacity'] ?? 0)])
            ->filter(fn ($r) => $r['room'] !== '' && $r['capacity'] > 0)
            ->values();

        if ($rooms->isEmpty()) {
            Notification::make()->title('Add at least one room with capacity')->danger()->send();

            return;
        }

        $totalCapacity = $rooms->sum('capacity');
        $perRow = max(1, (int) ($state['seats_per_row'] ?? 5));

        $allocations = [];
        $roomIndex = 0;
        $seatInRoom = 0;

        foreach ($students as $student) {
            // Advance to a room with remaining capacity.
            while ($roomIndex < $rooms->count() && $seatInRoom >= $rooms[$roomIndex]['capacity']) {
                $roomIndex++;
                $seatInRoom = 0;
            }
            if ($roomIndex >= $rooms->count()) {
                break; // out of capacity
            }

            $room = $rooms[$roomIndex]['room'];
            $seatNo = $seatInRoom + 1;
            $row = intdiv($seatInRoom, $perRow) + 1;
            $col = ($seatInRoom % $perRow) + 1;

            $allocations[] = [
                'exam_id'       => $examId,
                'student_id'    => $student->id,
                'class_id'      => $student->class_id,
                'section_id'    => $student->section_id,
                'room'          => $room,
                'seat_number'   => $room . '-' . $seatNo,
                'row_number'    => $row,
                'column_number' => $col,
            ];

            $seatInRoom++;
        }

        $allocated = count($allocations);
        $userId = auth()->guard('school_users')->id();

        DB::transaction(function () use ($examId, $students, $allocations, $userId): void {
            // Clear any previous plan for this exam + these students.
            ExamSeatPlan::where('exam_id', $examId)
                ->whereIn('student_id', $students->pluck('id'))
                ->delete();

            foreach ($allocations as $a) {
                $a['created_by'] = $userId;
                ExamSeatPlan::create($a);
            }
        });

        $unseated = $students->count() - $allocated;
        $msg = "Allocated {$allocated} student(s) across {$rooms->count()} room(s).";
        if ($unseated > 0) {
            $msg .= " {$unseated} could not be seated — total capacity ({$totalCapacity}) is below student count.";
        }

        Notification::make()
            ->title('Seat plan generated')
            ->body($msg)
            ->success()
            ->send();
    }

    public function download(): ?StreamedResponse
    {
        $examId = $this->data['exam_id'] ?? null;
        if (! $examId) {
            Notification::make()->title('Generate a plan first')->warning()->send();

            return null;
        }

        $exam = Exam::with('academicYear')->find($examId);
        if (! $exam) {
            return null;
        }

        $seats = ExamSeatPlan::with(['student.schoolClass', 'schoolClass', 'section'])
            ->where('exam_id', $examId)
            ->orderBy('room')
            ->orderBy('row_number')
            ->orderBy('column_number')
            ->get();

        if ($seats->isEmpty()) {
            Notification::make()->title('No seat plan to print — generate one first')->warning()->send();

            return null;
        }

        $byRoom = $seats->groupBy('room');

        $pdf = Pdf::loadView('pdf.seat-plan', [
            'exam'       => $exam,
            'byRoom'     => $byRoom,
            'schoolName' => tenant()?->school_name ?? config('app.name'),
        ])->setPaper('a4');

        $bytes = $pdf->output();
        $filename = 'seat-plan-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn () => print($bytes),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function getSeatSummary(): array
    {
        $examId = $this->data['exam_id'] ?? null;
        if (! $examId) {
            return [];
        }

        return ExamSeatPlan::where('exam_id', $examId)
            ->selectRaw('room, count(*) as total')
            ->groupBy('room')
            ->orderBy('room')
            ->pluck('total', 'room')
            ->toArray();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Seat Plan')
                ->icon('heroicon-o-sparkles')
                ->submit('generate'),

            Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action('download'),
        ];
    }
}
