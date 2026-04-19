<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AttendanceStatus;
use App\Enums\StudentFeeStatus;
use App\Models\Tenant;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\ExamResult;
use App\Models\Tenant\Notice;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use App\Models\Tenant\StudentGuardian;
use App\Services\Ai\OpenRouterService;
use App\Services\WhatsApp\WhatsAppServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ProcessWhatsAppMessage — Handles incoming WhatsApp messages from parents.
 *
 * This job is dispatched by the BotController when a parent sends
 * a WhatsApp message. It performs the following steps:
 *
 *   1. Identifies the parent by phone number (via StudentGuardian.phone/whatsapp
 *      or SchoolUser.whatsapp)
 *   2. Matches keywords in English/Urdu to determine the query type
 *   3. Queries the tenant database for the requested information
 *   4. Sends a formatted response back via WhatsApp
 *   5. Falls back to AI (OpenRouterService) when ai_enabled and no keyword match
 *
 * Supported keywords:
 *   - fee/fees/فیس/بقایا          → Fee status summary
 *   - attendance/حاضری/hazri      → Monthly attendance report
 *   - result/results/نتائج/رزلٹ    → Latest exam results
 *   - notice/notices/نوٹس/اعلان   → Recent school notices
 *   - help/مدد                    → Menu of available commands
 *
 * The job runs within the tenant context (tenant DB is already initialized
 * by the time this job executes, since the webhook is on a tenant route).
 */
class ProcessWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly string $phone,
        private readonly string $text,
        private readonly string $source,
    ) {}

    public function handle(): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            Log::warning('ProcessWhatsAppMessage: No tenant context', [
                'phone' => $this->phone,
            ]);
            return;
        }

        Log::info('ProcessWhatsAppMessage: Processing', [
            'phone'  => $this->phone,
            'text'   => $this->text,
            'source' => $this->source,
            'tenant' => $tenant->id,
        ]);

        // ── Step 1: Find the parent's children ──────────────────────
        $students = $this->findStudentsForParent();

        if ($students->isEmpty()) {
            $this->reply(
                $tenant,
                "⚠️ Sorry, we could not find any students linked to this phone number ({$this->phone}).\n\n"
                . "Please contact the school administration to link your WhatsApp number.\n\n"
                . "⚠️ معذرت، اس فون نمبر ({$this->phone}) سے کوئی طالب علم منسلک نہیں ملا۔\n"
                . "براہ کرم اپنا واٹس ایپ نمبر لنک کرنے کے لیے اسکول انتظامیہ سے رابطہ کریں۔"
            );
            return;
        }

        // ── Step 2: Match keywords ──────────────────────────────────
        $lower = mb_strtolower(trim($this->text));
        $intent = $this->detectIntent($lower);

        $response = match ($intent) {
            'fee'        => $this->handleFeeQuery($students),
            'attendance' => $this->handleAttendanceQuery($students),
            'result'     => $this->handleResultQuery($students),
            'notice'     => $this->handleNoticeQuery(),
            'help'       => $this->getHelpMenu(),
            'ai'         => $this->handleAiQuery($tenant, $students),
            default      => $this->getHelpMenu(),
        };

        $this->reply($tenant, $response);
    }

    /**
     * Detect the user's intent from their message text.
     */
    private function detectIntent(string $text): string
    {
        // Fee keywords (English + Urdu)
        $feePatterns = ['fee', 'fees', 'فیس', 'بقایا', 'challan', 'چالان', 'payment', 'ادائیگی'];
        foreach ($feePatterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return 'fee';
            }
        }

        // Attendance keywords
        $attendancePatterns = ['attendance', 'حاضری', 'hazri', 'absent', 'غیرحاضر', 'غیر حاضر'];
        foreach ($attendancePatterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return 'attendance';
            }
        }

        // Result keywords
        $resultPatterns = ['result', 'results', 'نتائج', 'رزلٹ', 'marks', 'نمبر', 'grade', 'exam'];
        foreach ($resultPatterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return 'result';
            }
        }

        // Notice keywords
        $noticePatterns = ['notice', 'notices', 'نوٹس', 'اعلان', 'announcement', 'اطلاع'];
        foreach ($noticePatterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return 'notice';
            }
        }

        // Help keywords
        $helpPatterns = ['help', 'مدد', 'menu', 'مینو', 'hi', 'hello', 'salam', 'سلام', 'start'];
        foreach ($helpPatterns as $pattern) {
            if ($text === $pattern || str_starts_with($text, $pattern)) {
                return 'help';
            }
        }

        // If tenant has AI enabled, fall back to AI
        if (tenant()?->ai_enabled) {
            return 'ai';
        }

        return 'help';
    }

    /**
     * Find students linked to the parent's phone number.
     *
     * Searches:
     *   1. StudentGuardian.phone or StudentGuardian.whatsapp
     *   2. Student → SchoolUser.whatsapp (for parents with portal access)
     */
    private function findStudentsForParent(): Collection
    {
        $normalizedPhone = $this->phone;

        // Try matching with and without the + prefix
        $phoneVariants = [
            $normalizedPhone,
            ltrim($normalizedPhone, '+'),
        ];

        // Also try with leading 0 for local Pakistan numbers
        $digits = ltrim($normalizedPhone, '+');
        if (str_starts_with($digits, '92')) {
            $phoneVariants[] = '0' . substr($digits, 2);
        }

        // Search in StudentGuardian
        $guardianStudentIds = StudentGuardian::query()
            ->where(function ($q) use ($phoneVariants) {
                $q->whereIn('phone', $phoneVariants)
                  ->orWhereIn('whatsapp', $phoneVariants);
            })
            ->pluck('student_id')
            ->unique();

        return Student::query()
            ->whereIn('id', $guardianStudentIds)
            ->where('status', 'enrolled')
            ->with(['schoolClass', 'section'])
            ->get();
    }

    // ── Query Handlers ──────────────────────────────────────────────

    /**
     * Handle fee status query — shows outstanding fees for each child.
     */
    private function handleFeeQuery(Collection $students): string
    {
        $lines = ["📋 *Fee Status / فیس کی تفصیلات*\n"];

        foreach ($students as $student) {
            $lines[] = "👤 *{$student->full_name}* ({$student->schoolClass?->name} - {$student->section?->name})";

            $unpaidFees = StudentFee::query()
                ->where('student_id', $student->id)
                ->whereIn('status', [StudentFeeStatus::Pending, StudentFeeStatus::Partial])
                ->with('feeType')
                ->orderBy('due_date')
                ->get();

            if ($unpaidFees->isEmpty()) {
                $lines[] = "  ✅ No pending fees / کوئی بقایا فیس نہیں\n";
                continue;
            }

            $totalBalance = 0;

            foreach ($unpaidFees as $fee) {
                $typeName = $fee->feeType?->name ?? 'Fee';
                $amount   = number_format($fee->amount_paisas / 100, 0);
                $paid     = number_format($fee->paid_paisas / 100, 0);
                $balance  = number_format($fee->balance_paisas / 100, 0);
                $due      = $fee->due_date?->format('d M Y') ?? 'N/A';
                $overdue  = $fee->due_date?->isPast() ? ' ⚠️ OVERDUE' : '';

                $lines[] = "  📌 {$typeName}: PKR {$amount}";
                $lines[] = "     Paid / ادا شدہ: PKR {$paid}";
                $lines[] = "     Balance / بقایا: PKR {$balance}{$overdue}";
                $lines[] = "     Due / واجب الادا: {$due}";

                $totalBalance += $fee->balance_paisas;
            }

            $totalFormatted = number_format($totalBalance / 100, 0);
            $lines[] = "  💰 *Total Due: PKR {$totalFormatted}*\n";
        }

        $lines[] = "\n📞 For payment queries, contact the school office.";
        $lines[] = "ادائیگی کے لیے اسکول دفتر سے رابطہ کریں۔";

        return implode("\n", $lines);
    }

    /**
     * Handle attendance query — shows current month's attendance summary.
     */
    private function handleAttendanceQuery(Collection $students): string
    {
        $month     = Carbon::now()->startOfMonth();
        $monthName = Carbon::now()->format('F Y');

        $lines = ["📊 *Attendance Report — {$monthName}*\n*حاضری رپورٹ*\n"];

        foreach ($students as $student) {
            $lines[] = "👤 *{$student->full_name}* ({$student->schoolClass?->name} - {$student->section?->name})";

            $records = AttendanceRecord::query()
                ->where('student_id', $student->id)
                ->whereBetween('date', [$month, Carbon::now()])
                ->get();

            if ($records->isEmpty()) {
                $lines[] = "  ℹ️ No attendance records for this month.\n";
                continue;
            }

            $present = $records->where('status', AttendanceStatus::Present)->count();
            $absent  = $records->where('status', AttendanceStatus::Absent)->count();
            $late    = $records->where('status', AttendanceStatus::Late)->count();
            $excused = $records->where('status', AttendanceStatus::Excused)->count();
            $total   = $records->count();

            $percentage = $total > 0
                ? round((($present + $late) / $total) * 100, 1)
                : 0;

            $lines[] = "  ✅ Present / حاضر: {$present}";
            $lines[] = "  ❌ Absent / غیر حاضر: {$absent}";
            $lines[] = "  ⏰ Late / دیر سے: {$late}";
            if ($excused > 0) {
                $lines[] = "  📝 Excused / رخصت: {$excused}";
            }
            $lines[] = "  📅 Total Working Days: {$total}";
            $lines[] = "  📈 Attendance: *{$percentage}%*";

            // Recent absences
            $recentAbsences = $records
                ->where('status', AttendanceStatus::Absent)
                ->sortByDesc('date')
                ->take(3);

            if ($recentAbsences->isNotEmpty()) {
                $lines[] = "\n  Recent Absences / حالیہ غیر حاضری:";
                foreach ($recentAbsences as $absence) {
                    $lines[] = "    • " . $absence->date->format('D, d M Y');
                }
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Handle result query — shows latest exam results for each child.
     */
    private function handleResultQuery(Collection $students): string
    {
        $lines = ["📝 *Exam Results / امتحانی نتائج*\n"];

        foreach ($students as $student) {
            $lines[] = "👤 *{$student->full_name}* ({$student->schoolClass?->name} - {$student->section?->name})";

            $results = ExamResult::query()
                ->where('student_id', $student->id)
                ->with('exam')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();

            if ($results->isEmpty()) {
                $lines[] = "  ℹ️ No published results yet.\n";
                continue;
            }

            foreach ($results as $result) {
                $examName    = $result->exam?->name ?? 'Exam';
                $obtained    = number_format((float) $result->marks_obtained, 1);
                $total       = number_format((float) $result->total_marks, 1);
                $percentage  = number_format((float) $result->percentage, 1);
                $grade       = $result->grade ?? '-';
                $status      = $result->status?->value ?? '-';
                $statusEmoji = strtolower($status) === 'pass' ? '✅' : '❌';
                $rank        = $result->rank ? "Rank: #{$result->rank}" : '';

                $lines[] = "  📌 *{$examName}*";
                $lines[] = "     Marks / نمبر: {$obtained} / {$total} ({$percentage}%)";
                $lines[] = "     Grade: {$grade} | {$statusEmoji} {$status} {$rank}";
            }

            $lines[] = '';
        }

        $lines[] = "📌 Only the 3 most recent exams are shown.";
        $lines[] = "صرف حالیہ 3 امتحانات دکھائے گئے ہیں۔";

        return implode("\n", $lines);
    }

    /**
     * Handle notice query — shows recent active school notices.
     */
    private function handleNoticeQuery(): string
    {
        $notices = Notice::query()
            ->active()
            ->forRole('parent')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        if ($notices->isEmpty()) {
            return "📢 *School Notices / اسکول نوٹس*\n\n"
                 . "ℹ️ No active notices at this time.\n"
                 . "اس وقت کوئی فعال نوٹس نہیں ہیں۔";
        }

        $lines = ["📢 *School Notices / اسکول نوٹس*\n"];

        foreach ($notices as $i => $notice) {
            $num   = $i + 1;
            $date  = $notice->published_at?->format('d M Y') ?? '';
            $title = $notice->title;

            // Truncate content to ~200 chars for WhatsApp readability
            $content = $notice->content;
            if (mb_strlen($content) > 200) {
                $content = mb_substr($content, 0, 200) . '...';
            }

            // Strip HTML tags if any
            $content = strip_tags($content);

            $lines[] = "📌 *{$num}. {$title}*";
            $lines[] = "   📅 {$date}";
            $lines[] = "   {$content}\n";
        }

        return implode("\n", $lines);
    }

    /**
     * Handle AI-powered free-form query using OpenRouter.
     */
    private function handleAiQuery(Tenant $tenant, Collection $students): string
    {
        try {
            $aiService = new OpenRouterService($tenant);

            // Build context about the parent's children
            $context = "Parent is asking about their children. Student information:\n";
            foreach ($students as $student) {
                $context .= "- {$student->full_name}, Class: {$student->schoolClass?->name}, "
                         . "Section: {$student->section?->name}\n";
            }

            $systemPrompt = "You are a helpful school assistant bot for parents on WhatsApp. "
                          . "You help parents with information about their children's education. "
                          . "Respond in the same language the parent used (English or Urdu). "
                          . "Keep responses concise (under 500 characters) for WhatsApp readability. "
                          . "If you don't know something specific, suggest they contact the school office. "
                          . "School name: {$tenant->school_name}. "
                          . "Available commands: fee, attendance, result, notice.";

            $response = $aiService->chat($systemPrompt, $this->text, $context);

            return "🤖 *AI Assistant*\n\n" . $response;

        } catch (\Throwable $e) {
            Log::warning('WhatsApp AI query failed', [
                'error' => $e->getMessage(),
                'phone' => $this->phone,
            ]);

            return $this->getHelpMenu();
        }
    }

    /**
     * Get the help menu with available commands.
     */
    private function getHelpMenu(): string
    {
        $schoolName = tenant()?->school_name ?? 'School';

        return "🏫 *{$schoolName}*\n"
             . "WhatsApp Bot / واٹس ایپ بوٹ\n\n"
             . "Send one of these keywords / ان میں سے ایک لفظ بھیجیں:\n\n"
             . "1️⃣ *fee* / *فیس* — Fee status / فیس کی تفصیلات\n"
             . "2️⃣ *attendance* / *حاضری* — Attendance report / حاضری رپورٹ\n"
             . "3️⃣ *result* / *نتائج* — Exam results / امتحانی نتائج\n"
             . "4️⃣ *notice* / *نوٹس* — School notices / اسکول نوٹس\n"
             . "5️⃣ *help* / *مدد* — This menu / یہ مینو\n\n"
             . "💡 You can type in English or Urdu.\n"
             . "آپ انگلش یا اردو میں لکھ سکتے ہیں۔";
    }

    /**
     * Send a reply to the parent via WhatsApp.
     */
    private function reply(Tenant $tenant, string $message): void
    {
        try {
            $whatsapp = WhatsAppServiceFactory::make($tenant);
            $whatsapp->sendText($this->phone, $message);

            Log::info('WhatsApp bot reply sent', [
                'phone'   => $this->phone,
                'length'  => mb_strlen($message),
                'tenant'  => $tenant->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsApp bot reply failed', [
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
