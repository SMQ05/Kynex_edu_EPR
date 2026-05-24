<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\ExamAttendanceRecord;
use App\Models\Tenant\ExamSchedule;
use App\Models\Tenant\Student;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ExamAttendanceEntry extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_exam_attendance';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Mark Exam Attendance';

    protected static ?string $title = 'Mark Exam Attendance';

    protected string $view = 'filament.school-admin.pages.exam-attendance-entry';

    public ?string $selectedScheduleId = null;

    public function mount(): void
    {
        $schedule = ExamSchedule::orderByDesc('exam_date')->first();
        $this->selectedScheduleId = $schedule?->id;
    }

    public function getScheduleOptions(): array
    {
        return ExamSchedule::with(['exam', 'schoolClass', 'section', 'subject'])
            ->orderByDesc('exam_date')
            ->get()
            ->mapWithKeys(fn (ExamSchedule $s) => [
                $s->id => ($s->exam?->name ?? 'Exam')
                    . ' · ' . ($s->schoolClass?->name ?? 'Class')
                    . ($s->section ? ' (' . $s->section->name . ')' : '')
                    . ' · ' . ($s->subject?->name ?? 'Subject')
                    . ' · ' . optional($s->exam_date)->format('d M Y'),
            ])
            ->toArray();
    }

    public function getSelectedSchedule(): ?ExamSchedule
    {
        if (! $this->selectedScheduleId) {
            return null;
        }

        return ExamSchedule::with(['exam', 'schoolClass', 'section', 'subject'])
            ->find($this->selectedScheduleId);
    }

    /** Students for the selected schedule's class/section, with current status. */
    public function getRoster(): Collection
    {
        $schedule = $this->getSelectedSchedule();
        if (! $schedule) {
            return collect();
        }

        $students = Student::query()
            ->where('class_id', $schedule->class_id)
            ->when($schedule->section_id, fn ($q) => $q->where('section_id', $schedule->section_id))
            ->where('status', 'enrolled')
            ->orderBy('roll_number')
            ->get();

        $existing = ExamAttendanceRecord::where('exam_schedule_id', $schedule->id)
            ->pluck('status', 'student_id');

        return $students->map(fn (Student $s) => (object) [
            'id'          => $s->id,
            'roll_number' => $s->roll_number,
            'name'        => $s->full_name,
            'status'      => $existing[$s->id] ?? null,
        ]);
    }

    /** Set/toggle one student's attendance. */
    public function setStatus(string $studentId, string $status): void
    {
        $schedule = $this->getSelectedSchedule();
        if (! $schedule) {
            return;
        }

        if (! in_array($status, ['present', 'absent', 'late', 'leave'], true)) {
            return;
        }

        ExamAttendanceRecord::updateOrCreate(
            ['exam_schedule_id' => $schedule->id, 'student_id' => $studentId],
            ['status' => $status],
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_all_present')
                ->label('Mark All Present')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => (bool) $this->selectedScheduleId)
                ->action(fn () => $this->markAll('present')),

            Action::make('mark_all_absent')
                ->label('Mark All Absent')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => (bool) $this->selectedScheduleId)
                ->action(fn () => $this->markAll('absent')),
        ];
    }

    protected function markAll(string $status): void
    {
        $schedule = $this->getSelectedSchedule();
        if (! $schedule) {
            return;
        }

        $count = 0;
        foreach ($this->getRoster() as $student) {
            ExamAttendanceRecord::updateOrCreate(
                ['exam_schedule_id' => $schedule->id, 'student_id' => $student->id],
                ['status' => $status],
            );
            $count++;
        }

        Notification::make()
            ->title("{$count} student(s) marked {$status}")
            ->success()
            ->send();
    }
}
