<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\Exam;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class SendMarksBySms extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'publish_results';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Send Marks by SMS';

    protected static ?string $title = 'Send Marks by SMS';

    protected string $view = 'filament.school-admin.pages.send-marks-by-sms';

    public ?string $exam_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;

    public function form(Schema $schema): Schema
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
                ->label('Section (optional)')
                ->options(fn () => $this->class_id
                    ? Section::where('class_id', $this->class_id)->pluck('name', 'id')
                    : [])
                ->reactive(),
        ]);
    }

    /** Results for the selected exam/class with student + guardians eager-loaded. */
    public function getResults(): Collection
    {
        if (! $this->exam_id) {
            return collect();
        }

        return ExamResult::with(['student.guardians', 'student.schoolClass', 'exam'])
            ->where('exam_id', $this->exam_id)
            ->when($this->class_id, fn ($q) => $q->where('class_id', $this->class_id))
            ->when($this->section_id, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section_id', $this->section_id)))
            ->get()
            ->filter(fn (ExamResult $r) => $r->student !== null)
            ->sortBy(fn (ExamResult $r) => $r->student->roll_number)
            ->values();
    }

    /** Build the SMS body for a single result. */
    protected function buildMessage(ExamResult $result): string
    {
        $student = $result->student;
        $exam    = $result->exam;
        $status  = $result->status?->value ?? (string) $result->status;

        return sprintf(
            'Dear Parent, %s scored %s/%s (%s%%) in %s. Grade: %s. Status: %s. — %s',
            $student->full_name,
            rtrim(rtrim((string) $result->marks_obtained, '0'), '.'),
            rtrim(rtrim((string) $result->total_marks, '0'), '.'),
            rtrim(rtrim((string) $result->percentage, '0'), '.'),
            $exam?->name ?? 'Exam',
            $result->grade ?? '—',
            ucfirst($status),
            tenant()?->school_name ?? config('app.name'),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Marks SMS')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Send marks to guardians by SMS?')
                ->modalDescription('An SMS will be sent to each student\'s primary guardian (falling back to any guardian with a phone). SMS charges may apply.')
                ->visible(fn () => (bool) $this->exam_id)
                ->action(fn () => $this->send()),
        ];
    }

    public function send(): void
    {
        $results = $this->getResults();
        if ($results->isEmpty()) {
            Notification::make()->title('No results to send — publish/compute results first')->warning()->send();

            return;
        }

        $service = app(NotificationService::class);
        $sent = 0;
        $skipped = 0;

        foreach ($results as $result) {
            $guardian = $result->student->guardians
                ->sortByDesc(fn ($g) => $g->is_primary_contact ? 1 : 0)
                ->first(fn ($g) => filled($g->phone));

            if (! $guardian) {
                $skipped++;
                continue;
            }

            try {
                $service->sendRaw(
                    channel: 'sms',
                    notifiable: $guardian,
                    body: $this->buildMessage($result),
                    eventTrigger: 'exam_result_published',
                    variables: ['exam_id' => $result->exam_id, 'student_id' => $result->student_id],
                );
                $sent++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        Notification::make()
            ->title("Marks SMS dispatched: {$sent} sent, {$skipped} skipped")
            ->body($skipped > 0 ? 'Skipped students had no guardian phone or the SMS driver failed.' : null)
            ->success()
            ->send();
    }
}
