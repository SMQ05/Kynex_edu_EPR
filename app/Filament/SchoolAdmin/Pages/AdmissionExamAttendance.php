<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\AdmissionSession;
use App\Models\Tenant\AdmissionTestAttempt;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\StudentApplication;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AdmissionExamAttendance extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_exam_attendance';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Exam Attendance';

    protected static ?string $title = 'Exam Day Attendance';

    protected string $view = 'filament.school-admin.pages.admission-exam-attendance';

    public ?string $selectedSessionId = null;

    public function mount(): void
    {
        // Default to the earliest upcoming or today's test session.
        $session = AdmissionSession::where('type', 'test')
            ->whereNull('exam_started_at')
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at')
            ->first()
            ?? AdmissionSession::where('type', 'test')
                ->orderByDesc('scheduled_at')
                ->first();

        $this->selectedSessionId = $session?->id;
    }

    public function getSelectedSession(): ?AdmissionSession
    {
        if (! $this->selectedSessionId) {
            return null;
        }

        return AdmissionSession::with(['admissionTest', 'schoolClass'])->find($this->selectedSessionId);
    }

    public function getSessionOptions(): array
    {
        return AdmissionSession::with('schoolClass')
            ->where('type', 'test')
            ->orderByDesc('scheduled_at')
            ->get()
            ->mapWithKeys(fn ($s) => [
                $s->id => ($s->schoolClass?->name ?? 'All classes')
                    . ' — '
                    . $s->scheduled_at->format('d M Y H:i')
                    . ($s->exam_started_at ? ' ✓ Started' : ''),
            ])
            ->toArray();
    }

    public function getApplicants(): \Illuminate\Support\Collection
    {
        if (! $this->selectedSessionId) {
            return collect();
        }

        return StudentApplication::with(['testAttempts' => fn ($q) => $q->latest()])
            ->where('test_session_id', $this->selectedSessionId)
            ->orderBy('first_name')
            ->get();
    }

    /** Mark a single applicant as checked-in. */
    public function checkIn(string $applicationId): void
    {
        $session = $this->getSelectedSession();
        if (! $session) {
            return;
        }

        $app = StudentApplication::find($applicationId);
        if (! $app) {
            return;
        }

        // Find or create the attempt.
        $attempt = AdmissionTestAttempt::where('student_application_id', $applicationId)
            ->when($session->test_id, fn ($q) => $q->where('admission_test_id', $session->test_id))
            ->first();

        if (! $attempt && $session->test_id) {
            $attempt = AdmissionTestAttempt::create([
                'student_application_id' => $applicationId,
                'admission_test_id'      => $session->test_id,
                'attempt_token'          => \Illuminate\Support\Str::random(60),
                'status'                 => 'pending',
            ]);
        }

        if (! $attempt) {
            Notification::make()->title('No test linked to this session — cannot check in')->warning()->send();
            return;
        }

        // If exam already started, set started_at so they can access immediately.
        $update = ['checked_in_at' => now()];
        if ($session->exam_started_at && ! $attempt->started_at) {
            $duration = $attempt->test?->duration_minutes ?? 60;
            $update['started_at'] = $session->exam_started_at;
            $update['expires_at'] = $session->exam_started_at->copy()->addMinutes($duration);
            $update['status']     = 'started';
        }

        $attempt->update($update);

        Notification::make()
            ->title($app->full_name . ' checked in')
            ->success()
            ->send();
    }

    /** Undo a check-in. */
    public function undoCheckIn(string $applicationId): void
    {
        $session = $this->getSelectedSession();
        if (! $session || $session->exam_started_at) {
            Notification::make()->title('Cannot undo check-in after exam has started')->warning()->send();
            return;
        }

        $attempt = AdmissionTestAttempt::where('student_application_id', $applicationId)->first();
        if ($attempt) {
            $attempt->update(['checked_in_at' => null]);
        }

        $app = StudentApplication::find($applicationId);
        Notification::make()
            ->title(($app?->full_name ?? 'Applicant') . ' check-in undone')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start_exam')
                ->label('Start Exam')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Start Exam for All Checked-In Applicants?')
                ->modalDescription('This will unlock the exam simultaneously for everyone marked as present. This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Start Exam Now')
                ->visible(fn () => $this->selectedSessionId && ! $this->getSelectedSession()?->exam_started_at)
                ->action(function () {
                    $session = $this->getSelectedSession();
                    if (! $session || $session->exam_started_at) {
                        return;
                    }

                    $startedAt = now();
                    $session->update(['exam_started_at' => $startedAt]);

                    // Set started_at + expires_at on all checked-in attempts.
                    $checkedInAttempts = AdmissionTestAttempt::whereHas('application', fn ($q) =>
                        $q->where('test_session_id', $session->id)
                    )
                    ->whereNotNull('checked_in_at')
                    ->whereNull('started_at')
                    ->with('test')
                    ->get();

                    foreach ($checkedInAttempts as $attempt) {
                        $duration = $attempt->test?->duration_minutes ?? 60;
                        $attempt->update([
                            'started_at' => $startedAt,
                            'expires_at' => $startedAt->copy()->addMinutes($duration),
                            'status'     => 'started',
                        ]);
                    }

                    Notification::make()
                        ->title('Exam started — ' . $checkedInAttempts->count() . ' applicant(s) unlocked')
                        ->success()
                        ->send();
                }),

            Action::make('mark_all_present')
                ->label('Mark All Present')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Mark every applicant assigned to this session as checked in.')
                ->visible(fn () => $this->selectedSessionId && ! $this->getSelectedSession()?->exam_started_at)
                ->action(function () {
                    $session = $this->getSelectedSession();
                    if (! $session) {
                        return;
                    }

                    $apps = StudentApplication::where('test_session_id', $session->id)->get();
                    $count = 0;

                    foreach ($apps as $app) {
                        $attempt = AdmissionTestAttempt::where('student_application_id', $app->id)
                            ->when($session->test_id, fn ($q) => $q->where('admission_test_id', $session->test_id))
                            ->first();

                        if (! $attempt && $session->test_id) {
                            $attempt = AdmissionTestAttempt::create([
                                'student_application_id' => $app->id,
                                'admission_test_id'      => $session->test_id,
                                'attempt_token'          => \Illuminate\Support\Str::random(60),
                                'status'                 => 'pending',
                            ]);
                        }

                        if ($attempt && ! $attempt->checked_in_at) {
                            $attempt->update(['checked_in_at' => now()]);
                            $count++;
                        }
                    }

                    Notification::make()
                        ->title("{$count} applicant(s) marked as present")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function updatedSelectedSessionId(): void {}
}
