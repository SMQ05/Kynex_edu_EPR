<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\AdmissionTestResource\RelationManagers;

use App\Jobs\GradeAdmissionAnswersBatch;
use App\Models\Tenant\AdmissionTestAttempt;
use App\Services\AdmissionAiService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Resend\Client as ResendClient;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Attempts';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('info')
                ->label('')
                ->content('Attempts are created when applicants submit the online test. Use the actions on each row to AI-grade or manually adjust scores.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('application.full_name')->label('Applicant')->searchable(),
                TextColumn::make('temp_username')
                    ->label('Exam Login')
                    ->placeholder('Not generated')
                    ->copyable()
                    ->copyMessage('Username copied'),
                TextColumn::make('temp_creds_sent_at')
                    ->label('Email Sent')
                    ->dateTime('d M · H:i')
                    ->placeholder('—')
                    ->color('success'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'graded'    => 'success',
                        'submitted' => 'warning',
                        'started'   => 'primary',
                        'expired'   => 'danger',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('violation_count')
                    ->label('Violations')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->placeholder('0'),
                TextColumn::make('obtained_marks')->label('Score')->placeholder('—'),
                TextColumn::make('total_marks')->label('Out of'),
                TextColumn::make('percentage')
                    ->label('%')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) . '%' : '—'),
                IconColumn::make('needs_manual_grading')
                    ->label('Pending review')
                    ->boolean(),
                TextColumn::make('submitted_at')->label('Submitted')->dateTime('d M · H:i')->placeholder('—'),
            ])
            ->actions([
                Action::make('sendLoginEmail')
                    ->label(fn (AdmissionTestAttempt $record) => $record->temp_creds_sent_at ? 'Resend Login Email' : 'Send Login Email')
                    ->icon('heroicon-o-envelope')
                    ->color(fn (AdmissionTestAttempt $record) => $record->temp_creds_sent_at ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (AdmissionTestAttempt $record) => $record->temp_creds_sent_at
                        ? 'This will generate fresh credentials (invalidating any previous ones) and resend the email to the applicant.'
                        : 'This will generate exam-day login credentials and email them directly to the applicant via Resend.')
                    ->action(function (AdmissionTestAttempt $record) {
                        $record->load(['application', 'test']);
                        $app = $record->application;

                        if (! $app?->email) {
                            Notification::make()
                                ->title('No email address')
                                ->body('This applicant has no email address on record. Add one to the application first.')
                                ->danger()->send();
                            return;
                        }

                        try {
                            // Always generate fresh credentials so we have the plain password.
                            $password = $record->generateTempCredentials();
                            $record->refresh();

                            $test   = $record->test;
                            $tenant = tenant();

                            $loginUrl = rtrim(config('app.url'), '/') . '/exam-login?tenant=' . urlencode($tenant->id);

                            $html = view('emails.admission-exam-credentials', [
                                'schoolName'      => $tenant->school_name ?? 'School',
                                'applicantName'   => $app->full_name,
                                'username'        => $record->temp_username,
                                'password'        => $password,
                                'testName'        => $test?->name,
                                'scheduledDate'   => $test?->scheduled_date?->format('l, d M Y'),
                                'windowOpens'     => $test?->window_opens_at?->format('H:i'),
                                'windowCloses'    => $test?->window_closes_at?->format('H:i'),
                                'durationMinutes' => $test?->duration_minutes,
                                'loginUrl'        => $loginUrl,
                            ])->render();

                            app(ResendClient::class)->emails->send([
                                'from'    => config('mail.from.name', 'KynexEdu') . ' <' . config('mail.from.address', 'noreply@kynexsolutions.com') . '>',
                                'to'      => $app->email,
                                'subject' => 'Your Admission Test Login — ' . ($tenant->school_name ?? 'School'),
                                'html'    => $html,
                            ]);

                            $record->update(['temp_creds_sent_at' => now()]);

                            Notification::make()
                                ->title('Login email sent')
                                ->body("Credentials emailed to **{$app->email}**. Username: `{$record->temp_username}` · Password: `{$password}`")
                                ->success()
                                ->persistent()
                                ->send();

                        } catch (\Throwable $e) {
                            Log::warning('Exam credentials email failed', ['error' => $e->getMessage(), 'attempt' => $record->id]);
                            Notification::make()
                                ->title('Email failed to send')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    }),

                Action::make('aiGrade')
                    ->label('AI grade (batched)')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (AdmissionTestAttempt $record) => $record->needs_manual_grading && ! $record->isProctoringCancelled())
                    ->requiresConfirmation()
                    ->modalDescription('AI will grade every pending short-answer, essay, and math response in batches of 5, with a short gap between batches to avoid overloading the AI. The attempt total updates as each batch completes.')
                    ->action(function (AdmissionTestAttempt $record) {
                        try {
                            GradeAdmissionAnswersBatch::dispatch($record->id, 0, 5);
                            Notification::make()
                                ->title('AI grading queued')
                                ->body('Batched grading jobs have been dispatched. Results will update shortly.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::warning('AI grading dispatch failed', ['error' => $e->getMessage()]);
                            Notification::make()
                                ->title('AI grading failed to queue')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('viewAnswers')
                    ->label('View answers')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (AdmissionTestAttempt $record) => 'Answers · ' . ($record->application->full_name ?? '—'))
                    ->modalContent(fn (AdmissionTestAttempt $record) => view(
                        'filament.school-admin.partials.admission-attempt-answers',
                        ['attempt' => $record->load(['answers.question', 'test'])],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('viewViolations')
                    ->label('View violations')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (AdmissionTestAttempt $record) => ($record->violation_count ?? 0) > 0)
                    ->modalHeading(fn (AdmissionTestAttempt $record) => 'Violations · ' . ($record->application->full_name ?? '—'))
                    ->modalContent(fn (AdmissionTestAttempt $record) => view(
                        'filament.school-admin.partials.admission-attempt-violations',
                        ['attempt' => $record],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ]);
    }
}
