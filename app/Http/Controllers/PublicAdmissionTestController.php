<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\SchoolUser;
use App\Models\Tenant\AdmissionSession;
use App\Models\Tenant\AdmissionTestAnswer;
use App\Models\Tenant\AdmissionTestAttempt;
use App\Services\AdmissionScoringService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Token-based online entry test for admission applicants.
 *
 * Flow:
 *   1. School admin generates a token link (or temp credentials) for
 *      an application → AdmissionTestAttempt is created.
 *   2. Applicant opens /admission-test/{token} → sees instructions.
 *   3. Applicant clicks "Start" → started_at + expires_at are set.
 *   4. Exam runs inside a JS-locked page (fullscreen + visibility guard).
 *   5. Violations are POSTed to /violation; after threshold the exam
 *      is cancelled and admins receive a database notification.
 *   6. On submit, auto-graded items are scored immediately; essay/math
 *      answers are queued for AI batch grading.
 *   7. Results are shown according to the test's result_publication_mode.
 */
class PublicAdmissionTestController extends Controller
{
    /** Max violations before the exam is auto-cancelled. */
    private const VIOLATION_CANCEL_THRESHOLD = 3;

    protected function ensureTenant(Request $request): ?\App\Models\Tenant
    {
        if (tenancy()->initialized) {
            return tenant();
        }

        // Try POST body / query, then session, then HTTP Referer.
        $hint = $request->input('tenant');

        if (! $hint && $request->hasSession()) {
            $hint = $request->session()->get('tenant_id');
        }

        if (! $hint) {
            $referer = $request->headers->get('referer');
            if ($referer) {
                $query = parse_url($referer, PHP_URL_QUERY) ?: '';
                parse_str($query, $parts);
                $hint = $parts['tenant'] ?? null;
            }
        }

        // Last-resort: try to look up the attempt token in the route segment
        // and resolve the tenant from whichever DB knows it. This protects
        // links that lost the ?tenant= query (e.g. browser strips, redirect).
        if (! $hint) {
            $token = $request->route('token');
            if ($token) {
                foreach (\App\Models\Tenant::all() as $candidate) {
                    $found = $candidate->run(function () use ($token) {
                        return AdmissionTestAttempt::where('attempt_token', $token)->exists();
                    });
                    if ($found) {
                        tenancy()->initialize($candidate);
                        return $candidate;
                    }
                }
            }
        }

        if (! $hint) {
            return null;
        }

        $tenant = \App\Models\Tenant::find($hint);
        if (! $tenant) {
            return null;
        }
        tenancy()->initialize($tenant);
        return $tenant;
    }

    /** Waiting room — shown after login, before exam starts. */
    public function waiting(Request $request, string $token)
    {
        $tenant = $this->ensureTenant($request);
        if (! $tenant) {
            abort(404, 'Exam link expired or invalid. Please log in again.');
        }

        $attempt = AdmissionTestAttempt::with(['test', 'application'])
            ->where('attempt_token', $token)
            ->first();
        if (! $attempt) {
            abort(404, 'Exam attempt not found. Please log in again.');
        }

        if ($attempt->isSubmitted() || $attempt->isProctoringCancelled()) {
            return redirect()->route('public.admission-test.start', ['token' => $token]);
        }

        // If already started (exam running), send directly to test.
        if ($attempt->started_at) {
            return redirect()->route('public.admission-test.start', ['token' => $token]);
        }

        $session = $attempt->application?->test_session_id
            ? AdmissionSession::find($attempt->application->test_session_id)
            : null;

        return view('public.admission-test-waiting', [
            'tenant'  => tenant(),
            'attempt' => $attempt,
            'session' => $session,
        ]);
    }

    /** AJAX poll endpoint for the waiting room — returns current gate state. */
    public function waitingStatus(Request $request, string $token)
    {
        $this->ensureTenant($request);

        $attempt = AdmissionTestAttempt::with('application')
            ->where('attempt_token', $token)
            ->firstOrFail();

        $session = $attempt->application?->test_session_id
            ? AdmissionSession::find($attempt->application->test_session_id)
            : null;

        // If exam started and this applicant is checked in, redirect to test.
        if ($attempt->started_at) {
            return response()->json(['ready' => true, 'redirect' => route('public.admission-test.start', ['token' => $token])]);
        }

        return response()->json([
            'ready'          => false,
            'exam_started'   => (bool) $session?->exam_started_at,
            'checked_in'     => (bool) $attempt->checked_in_at,
            'scheduled_at'   => $session?->scheduled_at?->toIso8601String(),
        ]);
    }

    public function start(Request $request, string $token)
    {
        $this->ensureTenant($request);

        $attempt = AdmissionTestAttempt::with(['test.questions', 'application'])
            ->where('attempt_token', $token)
            ->firstOrFail();

        // If linked to a session that hasn't been started by admin, redirect to waiting room.
        if (! $attempt->started_at) {
            $sessionId = $attempt->application?->test_session_id;
            if ($sessionId) {
                $session = AdmissionSession::find($sessionId);
                if ($session && ! $session->exam_started_at) {
                    return redirect()->route('public.admission-test.waiting', ['token' => $token]);
                }
            }
        }

        // Proctoring cancellation
        if ($attempt->isProctoringCancelled()) {
            return view('public.admission-test-cancelled', [
                'tenant'  => tenant(),
                'attempt' => $attempt,
            ]);
        }

        if ($attempt->isSubmitted()) {
            return view('public.admission-test-finished', [
                'tenant'  => tenant(),
                'attempt' => $attempt,
            ]);
        }

        // Enforce exam window before the test starts.
        if (! $attempt->started_at) {
            $test = $attempt->test;
            if ($test && ! $test->isWindowOpen()) {
                return view('public.admission-test-window-closed', [
                    'tenant'  => tenant(),
                    'attempt' => $attempt,
                    'test'    => $test,
                ]);
            }
        }

        if (! $attempt->started_at) {
            return view('public.admission-test-intro', [
                'tenant'  => tenant(),
                'attempt' => $attempt,
            ]);
        }

        return $this->renderQuestions($attempt);
    }

    public function begin(Request $request, string $token)
    {
        $this->ensureTenant($request);

        $attempt = AdmissionTestAttempt::with('test')->where('attempt_token', $token)->firstOrFail();

        if ($attempt->isSubmitted() || $attempt->isProctoringCancelled()) {
            return redirect()->route('public.admission-test.start', ['token' => $token]);
        }

        // Re-check window on begin (not just on intro view).
        if (! $attempt->test->isWindowOpen()) {
            return redirect()->route('public.admission-test.start', ['token' => $token]);
        }

        if (! $attempt->started_at) {
            $duration = $attempt->test->duration_minutes ?? 60;
            $attempt->update([
                'started_at' => now(),
                'expires_at' => now()->addMinutes($duration),
                'status'     => 'started',
            ]);
        }

        return redirect()->route('public.admission-test.start', ['token' => $token]);
    }

    protected function renderQuestions(AdmissionTestAttempt $attempt)
    {
        $attempt->loadMissing(['test.questions']);

        if ($attempt->isExpired() && $attempt->status === 'started') {
            $this->finaliseAttempt($attempt);
            return view('public.admission-test-finished', [
                'tenant'  => tenant(),
                'attempt' => $attempt->fresh(),
            ]);
        }

        $questions = $attempt->test->questions;
        if ($attempt->test->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        return view('public.admission-test-take', [
            'tenant'    => tenant(),
            'attempt'   => $attempt,
            'questions' => $questions,
        ]);
    }

    public function submit(Request $request, string $token)
    {
        $this->ensureTenant($request);

        $attempt = AdmissionTestAttempt::with(['test.questions', 'application'])
            ->where('attempt_token', $token)
            ->firstOrFail();

        if ($attempt->isSubmitted() || $attempt->isProctoringCancelled()) {
            return redirect()->route('public.admission-test.start', ['token' => $token]);
        }

        $answers = (array) $request->input('answers', []);

        DB::transaction(function () use ($attempt, $answers) {
            AdmissionTestAnswer::where('attempt_id', $attempt->id)->delete();

            foreach ($attempt->test->questions as $q) {
                $raw = $answers[$q->id] ?? null;
                $answerText = is_string($raw) ? trim($raw) : null;
                $selectedOption = null;

                $isCorrect = null;
                $marksAwarded = null;

                if ($q->type === 'mcq' || $q->type === 'true_false') {
                    $selectedOption = $answerText;
                    if (filled($q->correct_answer)) {
                        $isCorrect = strcasecmp((string) $selectedOption, (string) $q->correct_answer) === 0;
                        $marksAwarded = $isCorrect ? (float) $q->marks : 0;
                    }
                } elseif ($q->type === 'short_answer') {
                    if (filled($q->correct_answer)) {
                        $isCorrect = strcasecmp((string) $answerText, (string) $q->correct_answer) === 0;
                        $marksAwarded = $isCorrect ? (float) $q->marks : 0;
                    }
                }
                // essay/math → null marks_awarded, queued for AI batch grading

                AdmissionTestAnswer::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $q->id,
                    'answer_text'     => $answerText,
                    'selected_option' => $selectedOption,
                    'is_correct'      => $isCorrect,
                    'marks_awarded'   => $marksAwarded,
                ]);
            }

            $this->finaliseAttempt($attempt);
        });

        return view('public.admission-test-finished', [
            'tenant'  => tenant(),
            'attempt' => $attempt->fresh(),
        ]);
    }

    /**
     * AJAX endpoint called by proctoring JS in admission-test-take.blade.php.
     * Records a visibility/focus-loss violation and cancels the exam
     * after VIOLATION_CANCEL_THRESHOLD is reached.
     */
    public function violation(Request $request, string $token)
    {
        $this->ensureTenant($request);

        $attempt = AdmissionTestAttempt::with(['test', 'application'])
            ->where('attempt_token', $token)
            ->firstOrFail();

        if ($attempt->isSubmitted() || $attempt->isProctoringCancelled()) {
            return response()->json(['status' => 'already_done']);
        }

        $type = $request->input('type', 'tab_switch');
        $count = $attempt->recordViolation($type);

        $cancelled = false;

        if ($count >= self::VIOLATION_CANCEL_THRESHOLD) {
            $attempt->update([
                'proctoring_cancelled_at' => now(),
                'status'                  => 'cancelled',
                'submitted_at'            => now(),
            ]);

            $cancelled = true;

            // Update application status back to EntryTestScheduled so
            // admin can reschedule if desired.
            $app = $attempt->application;
            if ($app) {
                $app->update(['status' => ApplicationStatus::EntryTestScheduled->value]);
            }

            $this->notifyAdminsOfCancellation($attempt);
        } elseif ($count === 1) {
            // First violation — notify admins so they can monitor.
            $this->notifyAdminsOfViolation($attempt, $count);
        }

        return response()->json([
            'status'     => $cancelled ? 'cancelled' : 'warned',
            'violations' => $count,
            'remaining'  => max(0, self::VIOLATION_CANCEL_THRESHOLD - $count),
            'cancelled'  => $cancelled,
        ]);
    }

    protected function finaliseAttempt(AdmissionTestAttempt $attempt): void
    {
        $attempt->loadMissing(['answers', 'test.questions', 'application']);

        $autoGradable = $attempt->test->questions->filter(fn ($q) => $q->isAutoGradable());
        $autoTotal = (float) $autoGradable->sum('marks');
        $obtained = (float) $attempt->answers->sum('marks_awarded');

        $needsManual = $attempt->test->questions
            ->contains(fn ($q) => ! $q->isAutoGradable());

        $totalMarks = (float) $attempt->test->total_marks;
        $percentage = $totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 2) : null;

        $attempt->update([
            'submitted_at'         => now(),
            'status'               => $needsManual ? 'submitted' : 'graded',
            'obtained_marks'       => $obtained,
            'total_marks'          => $totalMarks,
            'percentage'           => $percentage,
            'needs_manual_grading' => $needsManual,
            'graded_at'            => $needsManual ? null : now(),
        ]);

        $app = $attempt->application;
        if ($app) {
            $app->update([
                'entry_test_score' => $obtained,
                'status'           => ApplicationStatus::EntryTestTaken->value,
            ]);
            app(AdmissionScoringService::class)->evaluate($app->refresh());
        }

        // Dispatch AI batch grading if there are pending essay/math answers.
        if ($needsManual) {
            \App\Jobs\GradeAdmissionAnswersBatch::dispatch($attempt->id, 0, 5)
                ->delay(now()->addSeconds(5));
        }
    }

    private function notifyAdminsOfViolation(AdmissionTestAttempt $attempt, int $count): void
    {
        try {
            $admins = SchoolUser::all();
            $name = $attempt->application?->full_name ?? 'Unknown';
            $testName = $attempt->test?->name ?? 'Admission Test';

            Notification::make()
                ->title("Proctoring alert: {$name}")
                ->body("Violation #{$count} detected during \"{$testName}\". Exam will auto-cancel at " . self::VIOLATION_CANCEL_THRESHOLD . " violations.")
                ->warning()
                ->sendToDatabase($admins);
        } catch (\Throwable $e) {
            Log::warning('Could not send proctoring violation notification', ['error' => $e->getMessage()]);
        }
    }

    private function notifyAdminsOfCancellation(AdmissionTestAttempt $attempt): void
    {
        try {
            $admins = SchoolUser::all();
            $name = $attempt->application?->full_name ?? 'Unknown';
            $testName = $attempt->test?->name ?? 'Admission Test';

            Notification::make()
                ->title("Exam cancelled: {$name}")
                ->body("The admission test \"{$testName}\" was auto-cancelled after " . self::VIOLATION_CANCEL_THRESHOLD . ' proctoring violations.')
                ->danger()
                ->sendToDatabase($admins);
        } catch (\Throwable $e) {
            Log::warning('Could not send exam cancellation notification', ['error' => $e->getMessage()]);
        }
    }
}
