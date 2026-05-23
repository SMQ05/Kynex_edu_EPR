<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant\AdmissionSession;
use App\Models\Tenant\AdmissionTestAttempt;
use App\Models\Tenant\StudentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Resend\Client as ResendClient;

/**
 * Send exam-day login credentials to applicants assigned to an
 * admission session in batches to avoid API rate limits.
 *
 * Each dispatch handles $batchSize applicants. If more remain,
 * the job re-dispatches itself with the next offset after a
 * short delay.
 *
 * Dispatch timing (resolved by the caller):
 *   - Session within 7 days → dispatch immediately (no delay)
 *   - Session > 7 days away → dispatch with delay until 7 days before
 */
class SendExamCredentialsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $sessionId,
        public readonly int $offset = 0,
        public readonly int $batchSize = 10,
    ) {}

    public function handle(): void
    {
        // Initialize tenant context so we query the right DB.
        $tenant = \App\Models\Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        // QUEUE_CONNECTION=sync means this job may run inline inside a
        // request that already has tenancy initialized. Don't blow away
        // the caller's context — only manage the lifecycle WE created.
        $weInitialized = false;
        if (! tenancy()->initialized || tenant()?->id !== $tenant->id) {
            tenancy()->initialize($tenant);
            $weInitialized = true;
        }

        try {
            $session = AdmissionSession::find($this->sessionId);
            if (! $session) {
                return;
            }

            $fk = $session->type === 'interview' ? 'interview_session_id' : 'test_session_id';

            // Only applicants with an email address who haven't been sent credentials yet.
            $pending = StudentApplication::where($fk, $session->id)
                ->whereNotNull('email')
                ->whereDoesntHave('testAttempts', function ($q) {
                    $q->whereNotNull('temp_creds_sent_at');
                })
                ->skip($this->offset)
                ->take($this->batchSize)
                ->get();

            if ($pending->isEmpty()) {
                // Mark session as dispatched.
                $session->update(['credentials_dispatched_at' => now()]);
                return;
            }

            $resend   = app(ResendClient::class);
            $appUrl   = rtrim(config('app.url'), '/');
            $loginUrl = $appUrl . '/exam-login?tenant=' . urlencode($this->tenantId);
            $schoolName = $tenant->school_name ?? 'School';
            $fromAddress = config('mail.from.name', 'KynexEdu') . ' <' . config('mail.from.address', 'noreply@kynexsolutions.com') . '>';

            foreach ($pending as $application) {
                try {
                    // Find an existing unsent attempt, or create one if the session
                    // has a linked test and none exists yet.
                    $attempt = $application->testAttempts()
                        ->whereNull('temp_creds_sent_at')
                        ->when($session->test_id, fn ($q) => $q->where('admission_test_id', $session->test_id))
                        ->first();

                    if (! $attempt && $session->test_id) {
                        $attempt = AdmissionTestAttempt::create([
                            'student_application_id' => $application->id,
                            'admission_test_id'       => $session->test_id,
                            'attempt_token'           => \Illuminate\Support\Str::random(60),
                            'status'                  => 'pending',
                        ]);
                    }

                    if (! $attempt) {
                        continue;
                    }

                    $password = $attempt->generateTempCredentials();
                    $attempt->refresh();

                    $html = view('emails.admission-exam-credentials', [
                        'schoolName'      => $schoolName,
                        'applicantName'   => $application->full_name,
                        'username'        => $attempt->temp_username,
                        'password'        => $password,
                        'testName'        => $attempt->test?->name ?? ($session->type === 'interview' ? 'Interview' : 'Admission Test'),
                        'scheduledDate'   => $session->scheduled_at->format('l, d M Y'),
                        'windowOpens'     => $session->scheduled_at->format('H:i'),
                        'windowCloses'    => null,
                        'durationMinutes' => $attempt->test?->duration_minutes,
                        'loginUrl'        => $loginUrl,
                    ])->render();

                    $resend->emails->send([
                        'from'    => $fromAddress,
                        'to'      => $application->email,
                        'subject' => 'Your Admission Test Login — ' . $schoolName,
                        'html'    => $html,
                    ]);

                    $attempt->update(['temp_creds_sent_at' => now()]);

                } catch (\Throwable $e) {
                    Log::warning('SendExamCredentialsBatch: failed for application', [
                        'application_id' => $application->id,
                        'error'          => $e->getMessage(),
                    ]);
                }
            }

            // Dispatch next batch if there may be more.
            if ($pending->count() >= $this->batchSize) {
                static::dispatch(
                    $this->tenantId,
                    $this->sessionId,
                    $this->offset + $this->batchSize,
                    $this->batchSize,
                )->delay(now()->addSeconds(30));
            } else {
                $session->update(['credentials_dispatched_at' => now()]);
            }

        } finally {
            if ($weInitialized) {
                tenancy()->end();
            }
        }
    }

    /**
     * Dispatch at the correct time:
     *   - Session within 7 days → immediately
     *   - Session > 7 days away → delayed until 7 days before the session
     */
    public static function dispatchForSession(AdmissionSession $session, string $tenantId): void
    {
        $sevenDaysBefore = $session->scheduled_at->copy()->subDays(7);
        $now = now();

        if ($now->greaterThanOrEqualTo($sevenDaysBefore)) {
            // Within the window — send now.
            static::dispatch($tenantId, $session->id, 0, 10);
        } else {
            // Schedule to fire 7 days before the session.
            static::dispatch($tenantId, $session->id, 0, 10)
                ->delay($sevenDaysBefore);
        }
    }
}
