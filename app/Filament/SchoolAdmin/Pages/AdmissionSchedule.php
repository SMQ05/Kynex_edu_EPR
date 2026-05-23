<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Jobs\SendExamCredentialsBatch;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AdmissionSession;
use App\Models\Tenant\AdmissionTest;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\StudentApplication;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AdmissionSchedule extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_student_admissions';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Schedule';

    protected static ?string $title = 'Admission Schedule';

    protected string $view = 'filament.school-admin.pages.admission-schedule';

    /** Active academic year filter (default: latest active year). */
    public ?string $filterYearId = null;

    /** Session type filter. */
    public string $filterType = 'test';

    public function mount(): void
    {
        $year = AcademicYear::where('is_current', true)->latest()->first()
            ?? AcademicYear::latest()->first();

        $this->filterYearId = $year?->id;
    }

    public function getFilteredSessions(): \Illuminate\Support\Collection
    {
        if (! $this->filterYearId) {
            return collect();
        }

        // If tenancy got lost (e.g. a sync queue job ended it mid-request),
        // attempt to restore it from the session before querying tenant tables.
        if (! tenancy()->initialized) {
            $tenantId = session('tenant_id');
            if ($tenantId && ($t = \App\Models\Tenant::find($tenantId))) {
                tenancy()->initialize($t);
            } else {
                return collect();
            }
        }

        try {
            return AdmissionSession::with(['schoolClass', 'applications', 'admissionTest'])
                ->where('academic_year_id', $this->filterYearId)
                ->where('type', $this->filterType)
                ->orderBy('scheduled_at')
                ->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AdmissionSchedule getFilteredSessions failed', [
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }

    public function getYearOptions(): array
    {
        return AcademicYear::orderByDesc('start_date')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_session')
                ->label('New Session')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    Select::make('type')
                        ->options(['test' => 'Admission Test', 'interview' => 'Interview'])
                        ->default('test')
                        ->required()
                        ->live(),

                    Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(AcademicYear::orderByDesc('start_date')->pluck('name', 'id'))
                        ->default(fn () => $this->filterYearId)
                        ->required(),

                    Select::make('class_id')
                        ->label('Class (leave blank for all classes)')
                        ->options(SchoolClass::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    Select::make('test_id')
                        ->label('Admission Test (students will sit this test)')
                        ->options(fn ($get) => AdmissionTest::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->helperText('Pick the test so login credentials can be auto-sent to applicants.')
                        ->visible(fn ($get) => $get('type') === 'test'),

                    DateTimePicker::make('scheduled_at')
                        ->label('Date & Time')
                        ->required()
                        ->native(false),

                    TextInput::make('max_capacity')
                        ->label('Max students in this slot')
                        ->numeric()
                        ->minValue(1)
                        ->default(30)
                        ->required()
                        ->suffix('students'),

                    TextInput::make('room')
                        ->label('Room / Venue')
                        ->maxLength(100),

                    TextInput::make('panel')
                        ->label('Panel / Invigilator')
                        ->maxLength(255)
                        ->visible(fn ($get) => $get('type') === 'interview'),

                    Textarea::make('notes')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $session = AdmissionSession::create([
                        'academic_year_id' => $data['academic_year_id'],
                        'class_id'         => ($data['class_id'] ?? null) ?: null,
                        'type'             => $data['type'],
                        'test_id'          => ($data['test_id'] ?? null) ?: null,
                        'scheduled_at'     => $data['scheduled_at'],
                        'max_capacity'     => $data['max_capacity'],
                        'room'             => $data['room'] ?? null,
                        'panel'            => $data['panel'] ?? null,
                        'notes'            => $data['notes'] ?? null,
                    ]);

                    // Auto-assign unscheduled applicants up to max_capacity.
                    $fk      = $data['type'] === 'interview' ? 'interview_session_id' : 'test_session_id';
                    $statusRequired = $data['type'] === 'interview' ? 'interview_scheduled' : 'test_scheduled';

                    $pending = StudentApplication::whereNull($fk)
                        ->where('academic_year_id', $data['academic_year_id'])
                        ->when($data['class_id'], fn ($q) => $q->where('class_id', $data['class_id']))
                        ->whereNotIn('status', ['approved', 'rejected', 'waitlisted'])
                        ->orderBy('created_at')
                        ->take($data['max_capacity'])
                        ->get();

                    $pending->each(fn ($app) => $app->update([$fk => $session->id]));

                    $assigned = $pending->count();

                    // Dispatch credential emails (respects 7-day window).
                    $tenantId = tenancy()->initialized ? tenant()->id : '';
                    if ($tenantId && $data['type'] === 'test') {
                        SendExamCredentialsBatch::dispatchForSession($session, $tenantId);
                    }

                    Notification::make()
                        ->title("Session created — {$assigned} applicant(s) assigned")
                        ->success()
                        ->send();

                    // Refresh the type filter to the just-created type so user sees the result.
                    $this->filterType = $data['type'];
                    $this->filterYearId = $data['academic_year_id'];
                }),

            Action::make('delete_session')
                ->label('Delete Session')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Choose a session to delete. Applicants will be unlinked (not deleted).')
                ->form([
                    Select::make('session_id')
                        ->label('Session')
                        ->options(function () {
                            return AdmissionSession::with('schoolClass')
                                ->where('academic_year_id', $this->filterYearId)
                                ->where('type', $this->filterType)
                                ->orderBy('scheduled_at')
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => ($s->schoolClass?->name ?? 'All classes') . ' — ' . $s->scheduled_at->format('d M Y H:i'),
                                ]);
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    $session = AdmissionSession::find($data['session_id']);
                    if (! $session) {
                        return;
                    }

                    // Unlink applicants.
                    $fk = $session->type === 'interview' ? 'interview_session_id' : 'test_session_id';
                    StudentApplication::where($fk, $session->id)->update([$fk => null]);

                    $session->delete();

                    Notification::make()->title('Session deleted')->success()->send();
                }),
        ];
    }

    public function updatedFilterYearId(): void {}

    public function updatedFilterType(): void {}

    /**
     * Return the credential-delivery breakdown for one session.
     * Used by the per-session "Delivery Report" panel in the blade.
     *
     * Categories:
     *   - sent      — applicant has email AND a TestAttempt with temp_creds_sent_at
     *   - pending   — applicant has email but no sent-attempt yet (will be sent later)
     *   - no_email  — applicant has no email on file (silently skipped by the job)
     *   - admitted  — already admitted; doesn't need credentials
     */
    public function getSessionDeliveryReport(string $sessionId): array
    {
        $empty = ['sent' => [], 'pending' => [], 'no_email' => [], 'admitted' => []];

        try {
            $session = AdmissionSession::find($sessionId);
            if (! $session) {
                return $empty;
            }

            $fk = $session->type === 'interview' ? 'interview_session_id' : 'test_session_id';

            // first_name / last_name are real columns; full_name is an accessor.
            $apps = StudentApplication::with('testAttempts')
                ->where($fk, $session->id)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            $sent = $pending = $no_email = $admitted = [];

            foreach ($apps as $a) {
                $statusValue = $a->status instanceof \App\Enums\ApplicationStatus
                    ? $a->status->value
                    : (string) $a->status;
                $isAdmitted = in_array($statusValue, ['admitted', 'approved'], true);

                $hasEmail   = filled($a->email);
                $sentAttempt = $a->testAttempts
                    ->whereNotNull('temp_creds_sent_at')
                    ->when($session->test_id, fn ($c) => $c->where('admission_test_id', $session->test_id))
                    ->first();

                $base = ['name' => $a->full_name, 'email' => $a->email, 'application_id' => $a->id];

                if ($isAdmitted && ! $sentAttempt) {
                    $admitted[] = $base;
                } elseif (! $hasEmail) {
                    $no_email[] = $base;
                } elseif ($sentAttempt) {
                    $sent[] = $base + [
                        'sent_at'  => $sentAttempt->temp_creds_sent_at?->format('d M H:i'),
                        'username' => $sentAttempt->temp_username,
                    ];
                } else {
                    $pending[] = $base;
                }
            }

            return compact('sent', 'pending', 'no_email', 'admitted');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('getSessionDeliveryReport failed', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);
            return $empty;
        }
    }

    /**
     * Re-dispatch the credential email job for applicants in a session who
     * still don't have credentials sent. Skips applicants without an email.
     */
    public function resendMissingCredentials(string $sessionId): void
    {
        $session = AdmissionSession::find($sessionId);
        if (! $session) {
            Notification::make()->title('Session not found')->danger()->send();
            return;
        }
        if ($session->type !== 'test') {
            Notification::make()->title('Only test sessions send credentials')->warning()->send();
            return;
        }

        $tenantId = tenancy()->initialized ? tenant()->id : null;
        if (! $tenantId) {
            Notification::make()->title('No tenant context')->danger()->send();
            return;
        }

        // Reset the dispatched-at flag and re-fire the batch.
        $session->update(['credentials_dispatched_at' => null]);
        SendExamCredentialsBatch::dispatch($tenantId, $session->id, 0, 10);

        Notification::make()
            ->title('Credentials re-dispatch queued')
            ->body('Pending applicants with email will receive credentials shortly.')
            ->success()
            ->send();
    }
}
