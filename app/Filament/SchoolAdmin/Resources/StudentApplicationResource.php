<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Enums\ApplicationStatus;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AdmissionCriteria;
use App\Models\Tenant\AdmissionTest;
use App\Models\Tenant\AdmissionTestAttempt;
use App\Models\Tenant\Campus;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\StudentApplication;
use App\Services\AdmissionDelegationService;
use App\Services\AdmissionScoringService;
use App\Services\RunnerUpService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class StudentApplicationResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_students';

    protected static ?string $model = StudentApplication::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Admissions';

    protected static string | \UnitEnum | null $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Application';

    protected static ?string $pluralModelLabel = 'Admissions';

    /**
     * Restrict the Admissions list to applications in classes the user
     * has been delegated to act on. SCHOOL_ADMIN, INSTITUTE_HEAD, and
     * MULTI_INSTITUTE_HEAD bypass the filter and see everything.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth('school_users')->user();
        if (! $user) {
            return $query;
        }

        // ── Campus scope: SCHOOL_ADMIN (and other campus-locked roles)
        //    must only see applications submitted to their own campus.
        //    INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD bypass.
        if (! $user->hasAnyRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'])) {
            if ($user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            }
        }

        // ── Class-delegation scope: extra filter when the user has
        //    been delegated to specific classes only.
        $allowed = app(AdmissionDelegationService::class)->combinedAllowedClassIds($user);

        if ($allowed === null) {
            // Unlimited — admins or fully-permissioned users.
            return $query;
        }

        if ($allowed === []) {
            // No delegation but the user reached the resource somehow
            // (e.g. legacy view_students grant) — show nothing rather
            // than everything to be safe.
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('class_id', $allowed);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = StudentApplication::query()
                ->whereIn('status', [
                    ApplicationStatus::Submitted->value,
                    ApplicationStatus::EntryTestScheduled->value,
                    ApplicationStatus::EntryTestTaken->value,
                    ApplicationStatus::InterviewScheduled->value,
                    ApplicationStatus::InterviewTaken->value,
                    ApplicationStatus::PendingApproval->value,
                ])
                ->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Applicant')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('first_name')->required(),
                    Components\TextInput::make('last_name')->required(),
                    Components\DatePicker::make('date_of_birth'),
                    Components\Select::make('gender')->options([
                        'male' => 'Male', 'female' => 'Female', 'other' => 'Other',
                    ]),
                    Components\TextInput::make('phone')->tel(),
                    Components\TextInput::make('email')->email(),
                    Components\TextInput::make('address')->columnSpanFull(),
                    Components\TextInput::make('city'),
                    Components\TextInput::make('previous_school'),
                ]),

            Section::make('Guardian')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('father_name'),
                    Components\TextInput::make('mother_name'),
                    Components\TextInput::make('guardian_phone')->tel(),
                    Components\TextInput::make('guardian_email')->email(),
                ]),

            Section::make('Class & Campus')
                ->columns(3)
                ->schema([
                    Components\Select::make('academic_year_id')
                        ->options(fn () => AcademicYear::pluck('name', 'id'))
                        ->searchable(),
                    Components\Select::make('class_id')
                        ->label('Class')
                        ->options(fn () => SchoolClass::pluck('name', 'id'))
                        ->searchable(),
                    Components\Select::make('campus_id')
                        ->options(fn () => Campus::pluck('name', 'id'))
                        ->searchable()
                        ->default(function () {
                            $user = auth('school_users')->user();
                            if ($user && ! $user->hasAnyRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'])) {
                                return $user->campus_id;
                            }
                            return null;
                        })
                        ->disabled(function () {
                            $user = auth('school_users')->user();
                            return $user
                                && ! $user->hasAnyRole(['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'])
                                && $user->campus_id;
                        })
                        ->dehydrated(),
                ]),

            Section::make('Entry Test')
                ->columns(2)
                ->schema([
                    Components\DateTimePicker::make('entry_test_scheduled_at')->label('Test scheduled at'),
                    Components\TextInput::make('entry_test_room'),
                    Components\TextInput::make('entry_test_score')->numeric()->step('0.01'),
                    Components\Textarea::make('entry_test_notes')->columnSpanFull()->rows(2),
                ]),

            Section::make('Interview')
                ->columns(2)
                ->schema([
                    Components\DateTimePicker::make('interview_scheduled_at')->label('Interview at'),
                    Components\TextInput::make('interview_room'),
                    Components\TextInput::make('interview_panel')
                        ->label('Panel / Interviewer')
                        ->placeholder('e.g. Principal + Vice Principal'),
                    Components\TextInput::make('interview_score')->numeric()->step('0.01'),
                    Components\Textarea::make('interview_notes')->columnSpanFull()->rows(2),
                ]),

            Section::make('Previous Academic Record')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('previous_school_score')->numeric()->step('0.01')->label('Previous score / marks'),
                    Components\TextInput::make('previous_score_max')->numeric()->step('0.01')->label('Out of'),
                ]),

            Section::make('Decision')
                ->columns(1)
                ->schema([
                    Components\Select::make('status')
                        ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->required(),
                    Components\Textarea::make('decision_notes')->rows(2),
                    Components\Placeholder::make('final_percentage_display')
                        ->label('Computed final percentage')
                        ->content(fn ($record) => $record?->final_percentage !== null
                            ? number_format((float) $record->final_percentage, 2) . '%'
                            : '— (run "Compute score" after marks are recorded)'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Applicant')
                    ->formatStateUsing(fn ($state, $record) => $record?->full_name ?? $state)
                    ->description(fn ($record) => $record
                        ? trim(
                            ($record->date_of_birth ? \Carbon\Carbon::parse($record->date_of_birth)->age . ' yrs' : '')
                            . ($record->gender ? ' · ' . ucfirst($record->gender) : '')
                        ) : null)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('schoolClass.name')->label('Class')->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('campus.name')->label('Campus')->placeholder('—')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('guardian_phone')->label('Guardian phone')->toggleable(),
                Tables\Columns\TextColumn::make('entry_test_scheduled_at')
                    ->label('Test')
                    ->dateTime('d M · H:i')
                    ->placeholder('Not scheduled')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('entry_test_score')->label('Test score')->placeholder('—')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('interview_scheduled_at')
                    ->label('Interview')
                    ->dateTime('d M · H:i')
                    ->placeholder('Not scheduled')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('interview_score')->label('Interview score')->placeholder('—')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('final_percentage')
                    ->label('Final %')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) . '%' : '—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ApplicationStatus ? $state->label() : ucfirst((string) $state))
                    ->color(function ($state): string {
                        if ($state instanceof ApplicationStatus) return $state->color();
                        return 'gray';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Applied')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                Tables\Filters\SelectFilter::make('class_id')->relationship('schoolClass', 'name')->label('Class'),
                Tables\Filters\SelectFilter::make('campus_id')->relationship('campus', 'name')->label('Campus'),
            ])
            ->actions([
                EditAction::make(),

                // ── Lifecycle: Schedule entry test ───────────────────
                Action::make('scheduleTest')
                    ->label('Schedule test')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->visible(fn ($r) => self::statusValue($r) === 'submitted')
                    ->form([
                        Components\DateTimePicker::make('entry_test_scheduled_at')
                            ->label('Date & time')->required()->default(now()->addDays(7)),
                        Components\TextInput::make('entry_test_room')->label('Room / Hall')->placeholder('Hall A'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'entry_test_scheduled_at' => $data['entry_test_scheduled_at'],
                            'entry_test_room'         => $data['entry_test_room'] ?? null,
                            'status'                  => ApplicationStatus::EntryTestScheduled->value,
                        ]);
                        Notification::make()->title('Test scheduled')->success()->send();
                    }),

                // ── Lifecycle: Send online test link ─────────────────
                Action::make('sendOnlineTest')
                    ->label('Online test link')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->visible(fn ($r) => in_array(self::statusValue($r), ['submitted', 'entry_test_scheduled'], true))
                    ->action(function ($record) {
                        $test = AdmissionTest::resolveFor($record->academic_year_id, $record->class_id);
                        if (! $test) {
                            Notification::make()
                                ->title('No online test configured for this class/year')
                                ->body('Create one under Students → Admission Tests first.')
                                ->warning()->send();
                            return;
                        }

                        $attempt = AdmissionTestAttempt::firstOrCreate(
                            [
                                'student_application_id' => $record->id,
                                'admission_test_id'      => $test->id,
                            ],
                            [
                                'attempt_token' => Str::random(60),
                                'status'        => 'pending',
                                'total_marks'   => $test->total_marks,
                            ],
                        );

                        $url = route('public.admission-test.start', [
                            'token' => $attempt->attempt_token,
                        ]);

                        Notification::make()
                            ->title('Online test link generated')
                            ->body($url)
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                // ── Lifecycle: Record test score ─────────────────────
                Action::make('recordScore')
                    ->label('Record test score')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn ($r) => in_array(self::statusValue($r), [
                        'entry_test_scheduled', 'entry_test_taken',
                        'interview_scheduled', 'interview_taken',
                    ], true))
                    ->form([
                        Components\TextInput::make('entry_test_score')
                            ->label('Score')
                            ->numeric()->step('0.01')->required()
                            ->placeholder('e.g. 78.5'),
                        Components\Textarea::make('entry_test_notes')
                            ->label('Notes (optional)')->rows(2)->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'entry_test_score' => $data['entry_test_score'],
                            'entry_test_notes' => $data['entry_test_notes'] ?? null,
                            'status'           => ApplicationStatus::EntryTestTaken->value,
                        ]);
                        $app = app(AdmissionScoringService::class)->evaluate($record->fresh());
                        if ($app->auto_rejected) {
                            Notification::make()
                                ->title('Score recorded · auto-rejected')
                                ->body($app->auto_reject_reason)
                                ->danger()->send();
                        } else {
                            Notification::make()->title('Score recorded')->success()->send();
                        }
                    }),

                // ── Lifecycle: Schedule interview ────────────────────
                Action::make('scheduleInterview')
                    ->label('Schedule interview')
                    ->icon('heroicon-o-user-group')
                    ->color('primary')
                    ->visible(fn ($r) => self::canConductInterview() && in_array(self::statusValue($r), [
                        'entry_test_taken', 'interview_scheduled',
                    ], true))
                    ->form([
                        Components\DateTimePicker::make('interview_scheduled_at')
                            ->label('Date & time')->required()->default(now()->addDays(3)),
                        Components\TextInput::make('interview_room')->label('Room'),
                        Components\TextInput::make('interview_panel')
                            ->label('Panel / Interviewer')
                            ->placeholder('e.g. Principal + Vice Principal'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'interview_scheduled_at' => $data['interview_scheduled_at'],
                            'interview_room'         => $data['interview_room'] ?? null,
                            'interview_panel'        => $data['interview_panel'] ?? null,
                            'status'                 => ApplicationStatus::InterviewScheduled->value,
                        ]);
                        Notification::make()->title('Interview scheduled')->success()->send();
                    }),

                // ── Lifecycle: Record interview score ────────────────
                Action::make('recordInterviewScore')
                    ->label('Record interview score')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(fn ($r) => self::canConductInterview() && in_array(self::statusValue($r), [
                        'interview_scheduled', 'interview_taken',
                    ], true))
                    ->form([
                        Components\TextInput::make('interview_score')
                            ->label('Score')
                            ->numeric()->step('0.01')->required(),
                        Components\Textarea::make('interview_notes')->rows(2)->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'interview_score' => $data['interview_score'],
                            'interview_notes' => $data['interview_notes'] ?? null,
                            'status'          => ApplicationStatus::InterviewTaken->value,
                        ]);
                        $app = app(AdmissionScoringService::class)->evaluate($record->fresh());
                        if ($app->auto_rejected) {
                            Notification::make()
                                ->title('Interview score recorded · auto-rejected')
                                ->body($app->auto_reject_reason)
                                ->danger()->send();
                        } else {
                            Notification::make()->title('Interview score recorded')->success()->send();
                        }
                    }),

                // ── Compute weighted score on demand ─────────────────
                Action::make('computeScore')
                    ->label('Compute score')
                    ->icon('heroicon-o-calculator')
                    ->color('gray')
                    ->action(function ($record) {
                        $app = app(AdmissionScoringService::class)->evaluate($record);
                        $msg = $app->final_percentage !== null
                            ? 'Final: ' . number_format((float) $app->final_percentage, 2) . '%'
                            : 'No criteria configured for this academic year / class.';
                        Notification::make()->title('Score computed')->body($msg)->info()->send();
                    }),

                // ── Lifecycle: Recommend / Mark for institute approval ─
                Action::make('recommend')
                    ->label('Recommend')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('info')
                    ->visible(fn ($r) => in_array(self::statusValue($r), [
                        'entry_test_taken', 'interview_taken',
                    ], true))
                    ->form([
                        Components\Textarea::make('decision_notes')->rows(3)->required()->minLength(5)
                            ->placeholder('Recommendation for the institute head'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'decision_notes' => $data['decision_notes'],
                            'status'         => ApplicationStatus::PendingApproval->value,
                            'reviewed_at'    => now(),
                            'reviewed_by'    => auth('school_users')->id(),
                        ]);
                        Notification::make()->title('Sent to institute head for final decision')->success()->send();
                    }),

                ActionGroup::make([
                    Action::make('admit')
                        ->label('Admit')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn ($r) => ! in_array(self::statusValue($r), ['admitted', 'rejected'], true))
                        ->form(fn ($record) => self::overrideRequiresApproval($record, 'admitted') ? [
                            Components\Textarea::make('notes')->label('Reason for override')->required()->rows(3),
                        ] : [])
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => self::overrideRequiresApproval($record, 'admitted')
                            ? 'Override auto-decision: Admit?'
                            : 'Admit this applicant')
                        ->modalDescription(fn ($record) => self::overrideRequiresApproval($record, 'admitted')
                            ? 'The system auto-decided "' . self::autoDecisionLabel($record) . '". Admitting will create an approval request needing both the institute head and exam admin to approve before it is applied.'
                            : 'Creates the Student record, generates a registration number, and sends the parent portal invite.')
                        ->action(function ($record, array $data = []) {
                            if (self::overrideRequiresApproval($record, 'admitted')) {
                                self::submitOverrideApproval($record, 'admitted', $data['notes'] ?? null);
                                Notification::make()
                                    ->title('Override submitted for approval')
                                    ->body('Institute head and exam admin must both approve before the applicant is admitted.')
                                    ->info()
                                    ->send();
                                return;
                            }

                            try {
                                app(\App\Services\StudentApplicationService::class)->admit($record);
                                Notification::make()->title('Admitted · student record created')->success()->send();
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning('Admission failed', ['error' => $e->getMessage()]);
                                Notification::make()->title('Admission failed')->body($e->getMessage())->danger()->send();
                            }
                        }),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($r) => self::statusValue($r) !== 'rejected')
                        ->form([
                            Components\Textarea::make('decision_notes')->required()->rows(3)
                                ->label(fn ($record) => self::overrideRequiresApproval($record, 'rejected')
                                    ? 'Reason for override'
                                    : 'Reason'),
                        ])
                        ->modalHeading(fn ($record) => self::overrideRequiresApproval($record, 'rejected')
                            ? 'Override auto-decision: Reject?'
                            : 'Reject application')
                        ->modalDescription(fn ($record) => self::overrideRequiresApproval($record, 'rejected')
                            ? 'The system auto-decided "' . self::autoDecisionLabel($record) . '". Rejecting will create an approval request needing both the institute head and exam admin to approve before it is applied.'
                            : null)
                        ->action(function ($record, array $data) {
                            if (self::overrideRequiresApproval($record, 'rejected')) {
                                self::submitOverrideApproval($record, 'rejected', $data['decision_notes']);
                                Notification::make()
                                    ->title('Override submitted for approval')
                                    ->body('Institute head and exam admin must both approve before the applicant is rejected.')
                                    ->info()
                                    ->send();
                                return;
                            }

                            $wasAdmitted = $record->status === ApplicationStatus::Admitted;

                            $record->update([
                                'status'         => ApplicationStatus::Rejected->value,
                                'decision_notes' => $data['decision_notes'],
                                'reviewed_at'    => now(),
                                'reviewed_by'    => auth('school_users')->id(),
                            ]);

                            Notification::make()->title('Application rejected')->danger()->send();

                            // Promote the next waitlisted applicant when a confirmed seat opens up.
                            if ($wasAdmitted) {
                                $promoted = app(RunnerUpService::class)->promote($record);
                                if ($promoted) {
                                    Notification::make()
                                        ->title('Runner-up promoted')
                                        ->body('The next waitlisted applicant has been admitted and notified by email.')
                                        ->success()
                                        ->send();
                                }
                            }
                        }),
                    Action::make('waitlist')
                        ->label('Waitlist')
                        ->icon('heroicon-o-clock')
                        ->color('gray')
                        ->visible(fn ($r) => ! in_array(self::statusValue($r), ['admitted', 'rejected', 'waitlisted'], true))
                        ->form(fn ($record) => self::overrideRequiresApproval($record, 'waitlisted') ? [
                            Components\Textarea::make('notes')->label('Reason for override')->required()->rows(3),
                        ] : [])
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => self::overrideRequiresApproval($record, 'waitlisted')
                            ? 'Override auto-decision: Waitlist?'
                            : 'Waitlist this applicant')
                        ->modalDescription(fn ($record) => self::overrideRequiresApproval($record, 'waitlisted')
                            ? 'The system auto-decided "' . self::autoDecisionLabel($record) . '". Waitlisting will create an approval request needing both the institute head and exam admin to approve before it is applied.'
                            : null)
                        ->action(function ($record, array $data = []) {
                            if (self::overrideRequiresApproval($record, 'waitlisted')) {
                                self::submitOverrideApproval($record, 'waitlisted', $data['notes'] ?? null);
                                Notification::make()
                                    ->title('Override submitted for approval')
                                    ->body('Institute head and exam admin must both approve before the applicant is waitlisted.')
                                    ->info()
                                    ->send();
                                return;
                            }

                            $record->update([
                                'status'      => ApplicationStatus::Waitlisted->value,
                                'reviewed_at' => now(),
                            ]);
                            Notification::make()->title('Waitlisted')->info()->send();
                        }),
                ])->label('Decision')->icon('heroicon-o-ellipsis-vertical')->button()->size('sm'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkScheduleTest')
                        ->label('Schedule entry test for selected')
                        ->icon('heroicon-o-calendar-days')
                        ->color('primary')
                        ->form([
                            Components\DateTimePicker::make('entry_test_scheduled_at')
                                ->label('Date & time')->required()->default(now()->addDays(7)),
                            Components\TextInput::make('entry_test_room')->label('Room / Hall'),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $r) {
                                if (in_array(self::statusValue($r), ['admitted', 'rejected'], true)) {
                                    continue;
                                }
                                $r->update([
                                    'entry_test_scheduled_at' => $data['entry_test_scheduled_at'],
                                    'entry_test_room'         => $data['entry_test_room'] ?? null,
                                    'status'                  => ApplicationStatus::EntryTestScheduled->value,
                                ]);
                                $count++;
                            }
                            Notification::make()->title("Test scheduled for {$count} applicants")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulkScheduleInterview')
                        ->label('Schedule interview for selected')
                        ->icon('heroicon-o-user-group')
                        ->color('primary')
                        ->visible(fn () => self::canConductInterview())
                        ->form([
                            Components\DateTimePicker::make('interview_scheduled_at')
                                ->label('Date & time')->required()->default(now()->addDays(3)),
                            Components\TextInput::make('interview_room')->label('Room'),
                            Components\TextInput::make('interview_panel')->label('Panel / Interviewer'),
                        ])
                        ->action(function ($records, array $data) {
                            $count = 0;
                            foreach ($records as $r) {
                                if (in_array(self::statusValue($r), ['admitted', 'rejected'], true)) {
                                    continue;
                                }
                                $r->update([
                                    'interview_scheduled_at' => $data['interview_scheduled_at'],
                                    'interview_room'         => $data['interview_room'] ?? null,
                                    'interview_panel'        => $data['interview_panel'] ?? null,
                                    'status'                 => ApplicationStatus::InterviewScheduled->value,
                                ]);
                                $count++;
                            }
                            Notification::make()->title("Interview scheduled for {$count} applicants")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulkRecompute')
                        ->label('Recompute weighted score')
                        ->icon('heroicon-o-calculator')
                        ->action(function ($records) {
                            $svc = app(AdmissionScoringService::class);
                            foreach ($records as $r) {
                                $svc->evaluate($r);
                            }
                            Notification::make()->title('Scores recomputed')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /**
     * Coerce status to a plain string regardless of enum-or-not.
     */
    private static function statusValue($record): string
    {
        $s = $record->status ?? null;
        return $s instanceof \BackedEnum ? $s->value : (string) $s;
    }

    /**
     * Interview scheduling and scoring is gated on the interview
     * delegation permission (or an admin role bypass). Class-scoped
     * delegation rows count too.
     */
    private static function canConductInterview(): bool
    {
        return app(AdmissionDelegationService::class)->userHasPermission(
            auth('school_users')->user(),
            'conduct_admission_interview',
        );
    }

    /**
     * True when the system has already committed to an auto-decision and
     * the admin's chosen status would change that. In that case the
     * decision must route through the dual-approval flow.
     */
    private static function overrideRequiresApproval($record, string $intendedStatus): bool
    {
        $auto = $record->auto_decision_status ?? null;
        if (! $auto) {
            return false;
        }
        return $auto !== $intendedStatus;
    }

    private static function autoDecisionLabel($record): string
    {
        $auto = $record->auto_decision_status ?? null;
        if (! $auto) {
            return '—';
        }
        $enum = ApplicationStatus::tryFrom($auto);
        return $enum ? $enum->label() : ucfirst(str_replace('_', ' ', $auto));
    }

    private static function submitOverrideApproval($record, string $newStatus, ?string $notes): void
    {
        $requester = auth('school_users')->user() ?? \App\Models\SchoolUser::firstOrFail();

        app(\App\Services\ApprovalService::class)->submit(
            requestedBy: $requester,
            actionType:  'admission_decision_override',
            subject:     $record,
            payload:     [
                'application_id' => $record->getKey(),
                'new_status'     => $newStatus,
                'notes'          => $notes,
                'stage'          => 1,
            ],
            approverLevel: 'institute_head',
        );
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudentApplications::route('/'),
            'create' => Pages\CreateStudentApplication::route('/create'),
            'edit'   => Pages\EditStudentApplication::route('/{record}/edit'),
        ];
    }
}
