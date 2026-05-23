<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Enums\ApplicationStatus;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\AdmissionCriteria;
use App\Models\Tenant\StudentApplication;
use App\Services\AdmissionDelegationService;
use App\Services\AdmissionScoringService;
use App\Services\ApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;

/**
 * Dedicated workflow page for entering entry-test and interview marks
 * + dates against a specific applicant. Shows a read-only profile so
 * the marker has full context but cannot accidentally edit personal
 * details from this screen. Saving runs the scoring service which
 * auto-decides (Reject if below minima, PendingApproval if all met).
 */
class AdmissionMarksEntry extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'enter_admission_marks';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Marks & Interview';

    protected static ?string $title = 'Entry Test & Interview Marks';

    protected string $view = 'filament.school-admin.pages.admission-marks-entry';

    public ?string $applicationId = null;

    public ?array $data = [];

    public function mount(?string $applicationId = null): void
    {
        $this->applicationId = $applicationId
            ?? request()->query('application');

        $this->loadFormFromApplication();
    }

    public function updatedApplicationId(): void
    {
        $this->loadFormFromApplication();
    }

    protected function loadFormFromApplication(): void
    {
        $app = $this->resolveApplication();

        $this->form->fill([
            'entry_test_scheduled_at' => $app?->entry_test_scheduled_at,
            'entry_test_room'         => $app?->entry_test_room,
            'entry_test_score'        => $app?->entry_test_score,
            'entry_test_notes'        => $app?->entry_test_notes,
            'interview_scheduled_at'  => $app?->interview_scheduled_at,
            'interview_room'          => $app?->interview_room,
            'interview_panel'         => $app?->interview_panel,
            'interview_score'         => $app?->interview_score,
            'interview_notes'         => $app?->interview_notes,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $app = $this->resolveApplication();

        $testScoreLockState = $this->testScoreLockState($app);   // null|'auto'|'manual'
        $interviewLockState = $this->interviewScoreLockState($app); // null|'manual'

        return $schema
            ->statePath('data')
            ->schema([
                FormSection::make('Entry test')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('entry_test_scheduled_at')->label('Test scheduled at'),
                        TextInput::make('entry_test_room')->label('Room / Hall'),

                        TextInput::make('entry_test_score')
                            ->label('Score')
                            ->numeric()
                            ->step('0.01')
                            ->disabled($testScoreLockState !== null)
                            ->dehydrated($testScoreLockState === null)
                            ->helperText(match ($testScoreLockState) {
                                'auto'   => 'Auto-recorded from the online test — read-only. To correct, contact a SaaS admin.',
                                'manual' => 'Saved earlier. To change, use "Edit test marks (approval)" above — institute head and exam admin must both approve.',
                                default  => null,
                            }),

                        Textarea::make('entry_test_notes')->rows(2)->columnSpanFull(),
                    ]),

                FormSection::make('Interview')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('interview_scheduled_at')->label('Interview at'),
                        TextInput::make('interview_room'),
                        TextInput::make('interview_panel')->label('Panel / Interviewer'),

                        TextInput::make('interview_score')
                            ->label('Score')
                            ->numeric()
                            ->step('0.01')
                            ->disabled($interviewLockState !== null)
                            ->dehydrated($interviewLockState === null)
                            ->helperText(match ($interviewLockState) {
                                'manual' => 'Saved earlier. Use "Edit interview marks (approval)" above — institute head and exam admin must both approve.',
                                default  => null,
                            }),

                        Textarea::make('interview_notes')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Returns 'auto' if score came from an online test attempt, 'manual'
     * if manually saved, null if not yet recorded (still editable).
     */
    public function testScoreLockState(?StudentApplication $app): ?string
    {
        if (! $app || $app->entry_test_score === null) {
            return null;
        }
        return $app->wasTestScoreFromOnlineAttempt() ? 'auto' : 'manual';
    }

    public function interviewScoreLockState(?StudentApplication $app): ?string
    {
        if (! $app || $app->interview_score === null) {
            return null;
        }
        // Interviews are always manual — there's no online interview path.
        return 'manual';
    }

    /**
     * Header actions: edit-with-approval buttons that only show when the
     * matching field is in 'manual' lock state.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('editTestMarks')
                ->label('Edit test marks (approval)')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->visible(fn () => $this->testScoreLockState($this->resolveApplication()) === 'manual')
                ->modalHeading('Request approval to edit entry test score')
                ->modalDescription('This change will not take effect immediately. The institute head and the exam admin must both approve before the new score is applied.')
                ->form([
                    Placeholder::make('current')
                        ->label('Current score')
                        ->content(fn () => (string) ($this->resolveApplication()?->entry_test_score ?? '—')),
                    TextInput::make('new_value')
                        ->label('New score')
                        ->numeric()
                        ->step('0.01')
                        ->required(),
                    Textarea::make('reason')
                        ->label('Reason for change')
                        ->required()
                        ->rows(3)
                        ->minLength(10),
                ])
                ->action(function (array $data) {
                    $this->submitMarksOverride('entry_test_score', $data);
                }),

            Action::make('editInterviewMarks')
                ->label('Edit interview marks (approval)')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->visible(fn () => $this->interviewScoreLockState($this->resolveApplication()) === 'manual')
                ->modalHeading('Request approval to edit interview score')
                ->modalDescription('This change will not take effect immediately. The institute head and the exam admin must both approve before the new score is applied.')
                ->form([
                    Placeholder::make('current')
                        ->label('Current score')
                        ->content(fn () => (string) ($this->resolveApplication()?->interview_score ?? '—')),
                    TextInput::make('new_value')
                        ->label('New score')
                        ->numeric()
                        ->step('0.01')
                        ->required(),
                    Textarea::make('reason')
                        ->label('Reason for change')
                        ->required()
                        ->rows(3)
                        ->minLength(10),
                ])
                ->action(function (array $data) {
                    $this->submitMarksOverride('interview_score', $data);
                }),
        ];
    }

    protected function submitMarksOverride(string $field, array $data): void
    {
        $app = $this->resolveApplication();
        if (! $app) {
            Notification::make()->title('Pick an application first')->warning()->send();
            return;
        }

        $requester = auth('school_users')->user() ?? \App\Models\SchoolUser::firstOrFail();

        app(ApprovalService::class)->submit(
            requestedBy:    $requester,
            actionType:     'admission_marks_override',
            subject:        null,
            payload:        [
                'application_id' => $app->getKey(),
                'field'          => $field,
                'new_value'      => $data['new_value'],
                'reason'         => $data['reason'] ?? null,
                'stage'          => 1,
            ],
            approverLevel:  'institute_head',
        );

        Notification::make()
            ->title('Override submitted for approval')
            ->body('Institute head and exam admin must both approve before the new score is applied.')
            ->info()
            ->send();
    }

    /**
     * Save the form back onto the application and run the scoring + auto-
     * decision pipeline. Status flips reflect the auto-decision result.
     */
    public function save(): void
    {
        $app = $this->resolveApplication();
        if (! $app) {
            Notification::make()->title('Pick an application first')->warning()->send();
            return;
        }

        $state = $this->form->getState();

        // Status promotion follows the inputs available:
        //   only test scheduled         → EntryTestScheduled
        //   test scored                 → EntryTestTaken
        //   interview scheduled         → InterviewScheduled
        //   interview scored            → InterviewTaken (then auto-decision may move it further)
        $next = $app->status instanceof ApplicationStatus ? $app->status->value : (string) $app->status;
        if (! empty($state['entry_test_scheduled_at']) && in_array($next, ['submitted'], true)) {
            $next = ApplicationStatus::EntryTestScheduled->value;
        }
        $testScore = $state['entry_test_score'] ?? null;
        if ($testScore !== null && $testScore !== '') {
            $next = ApplicationStatus::EntryTestTaken->value;
        }
        if (! empty($state['interview_scheduled_at']) && in_array($next, ['entry_test_taken', 'submitted', 'entry_test_scheduled'], true)) {
            $next = ApplicationStatus::InterviewScheduled->value;
        }
        $interviewScore = $state['interview_score'] ?? null;
        if ($interviewScore !== null && $interviewScore !== '') {
            $next = ApplicationStatus::InterviewTaken->value;
        }

        $update = [
            'entry_test_scheduled_at' => ($state['entry_test_scheduled_at'] ?? null) ?: null,
            'entry_test_room'         => ($state['entry_test_room'] ?? null) ?: null,
            'entry_test_notes'        => ($state['entry_test_notes'] ?? null) ?: null,
            'interview_scheduled_at'  => ($state['interview_scheduled_at'] ?? null) ?: null,
            'interview_room'          => ($state['interview_room'] ?? null) ?: null,
            'interview_panel'         => ($state['interview_panel'] ?? null) ?: null,
            'interview_notes'         => ($state['interview_notes'] ?? null) ?: null,
            'status'                  => $next,
        ];

        // Score fields are only included when they are NOT locked; if a
        // score is already saved, the field is dehydrated(false) so the
        // value isn't even in $state. Belt-and-braces re-check here.
        if ($this->testScoreLockState($app) === null && array_key_exists('entry_test_score', $state)) {
            $update['entry_test_score'] = ($state['entry_test_score'] !== '' ? $state['entry_test_score'] : null);
        }
        if ($this->interviewScoreLockState($app) === null && array_key_exists('interview_score', $state)) {
            $update['interview_score'] = ($state['interview_score'] !== '' ? $state['interview_score'] : null);
        }

        $app->update($update);

        $app = app(AdmissionScoringService::class)->evaluate($app->refresh());

        $msg = match (true) {
            $app->auto_rejected                              => 'Saved · auto-rejected: ' . ($app->auto_reject_reason ?? 'below threshold'),
            $app->auto_decision_status === ApplicationStatus::PendingApproval->value
                => 'Saved · auto-promoted to Pending Approval (final ' . number_format((float) ($app->final_percentage ?? 0), 2) . '%)',
            default                                          => 'Saved · status: ' . ucfirst(str_replace('_', ' ', (string) $app->status->value)),
        };

        Notification::make()->title($msg)->success()->send();
    }

    public function resolveApplication(): ?StudentApplication
    {
        if (! $this->applicationId) {
            return null;
        }

        $app = StudentApplication::with(['schoolClass', 'campus', 'academicYear'])->find($this->applicationId);
        if (! $app) {
            return null;
        }

        // Enforce class scope on direct ID lookup too.
        $user = auth('school_users')->user();
        $allowed = app(AdmissionDelegationService::class)
            ->allowedClassIdsForUser($user, 'enter_admission_marks');
        if ($allowed !== null && ! in_array($app->class_id, $allowed, true)) {
            return null;
        }

        return $app;
    }

    /**
     * Used by the blade view to render a profile sidebar. Read-only.
     */
    public function getProfile(): array
    {
        $app = $this->resolveApplication();
        if (! $app) {
            return [];
        }

        return [
            'Name'              => $app->full_name,
            'Date of birth'     => optional($app->date_of_birth)?->format('d M Y') ?? '—',
            'Gender'            => $app->gender ? ucfirst($app->gender) : '—',
            'Phone'             => $app->phone ?? '—',
            'Email'             => $app->email ?? '—',
            'Address'           => $app->address ?? '—',
            'City'              => $app->city ?? '—',
            'Father'            => $app->father_name ?? '—',
            'Mother'            => $app->mother_name ?? '—',
            'Guardian phone'    => $app->guardian_phone ?? '—',
            'Guardian email'    => $app->guardian_email ?? '—',
            'Previous school'   => $app->previous_school ?? '—',
            'Class'             => optional($app->schoolClass)?->name ?? '—',
            'Campus'            => optional($app->campus)?->name ?? '—',
            'Academic year'     => optional($app->academicYear)?->name ?? '—',
        ];
    }

    /**
     * Snapshot of the current decision-relevant numbers for the UI panel.
     */
    public function getDecisionSnapshot(): array
    {
        $app = $this->resolveApplication();
        if (! $app) {
            return [];
        }

        $criteria = AdmissionCriteria::resolveFor($app->academic_year_id, $app->class_id);

        return [
            'status'             => $app->status instanceof ApplicationStatus ? $app->status->label() : (string) $app->status,
            'final_percentage'   => $app->final_percentage,
            'auto_rejected'      => $app->auto_rejected,
            'auto_reject_reason' => $app->auto_reject_reason,
            'auto_decision_at'   => $app->auto_decision_at,
            'criteria_present'   => (bool) $criteria,
            'min_test'           => $criteria?->min_test_score,
            'min_interview'      => $criteria?->min_interview_score,
            'min_final'          => $criteria?->min_final_percentage,
            'weights'            => $criteria ? [
                'test'      => $criteria->test_weightage,
                'interview' => $criteria->interview_weightage,
                'previous'  => $criteria->previous_score_weightage,
            ] : null,
        ];
    }

    public function getApplicationOptions(): array
    {
        $query = StudentApplication::query();

        // Class-scope: a delegated marker only sees applicants for their
        // assigned classes. SCHOOL_ADMIN/INSTITUTE_HEAD bypass.
        $user = auth('school_users')->user();
        $allowed = app(AdmissionDelegationService::class)
            ->allowedClassIdsForUser($user, 'enter_admission_marks');

        if ($allowed === []) {
            return [];
        }
        if (is_array($allowed)) {
            $query->whereIn('class_id', $allowed);
        }

        return $query
            ->orderByDesc('created_at')
            ->limit(500)
            ->get(['id', 'first_name', 'last_name', 'class_id', 'status'])
            ->mapWithKeys(fn (StudentApplication $a) => [
                $a->id => trim(
                    $a->full_name
                    . ' — ' . ($a->status instanceof ApplicationStatus ? $a->status->label() : (string) $a->status),
                ),
            ])
            ->all();
    }
}
