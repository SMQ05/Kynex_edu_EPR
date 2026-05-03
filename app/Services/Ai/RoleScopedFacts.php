<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\SchoolUser;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\AttendanceRecord;
use App\Models\Tenant\BehaviorIncident;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\Expense;
use App\Models\Tenant\HomeworkAssignment;
use App\Models\Tenant\Section;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentFee;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Builds a small JSON facts snapshot for the AI assistant, scoped to the
 * caller's role. Higher-trust roles (Multi-Institute Head, Institute Head,
 * School Admin) get school-wide aggregates; teachers get only their own
 * classes; students/parents get only their own (or their child's) records.
 *
 * The returned snapshot is prepended to the LLM's system prompt as a
 * "Live data" block, so the model can answer questions like "how many
 * students" with the right number — but never with data outside the
 * caller's authority.
 */
class RoleScopedFacts
{
    private const ADMIN_ROLES = ['MULTI_INSTITUTE_HEAD', 'INSTITUTE_HEAD', 'SCHOOL_ADMIN'];

    private const FINANCE_ROLES = ['MULTI_INSTITUTE_HEAD', 'INSTITUTE_HEAD', 'SCHOOL_ADMIN', 'BURSAR', 'ACCOUNTANT'];

    /**
     * Return a role-appropriate facts snapshot. Empty array when the role
     * has no specific live data to expose (the model still answers based
     * on its system prompt; it just won't have numbers).
     *
     * @return array<string,mixed>
     */
    public function for(SchoolUser $user, string $role): array
    {
        try {
            return match (true) {
                in_array($role, self::ADMIN_ROLES, true) => $this->adminFacts($role),
                in_array($role, self::FINANCE_ROLES, true) => $this->financeFacts(),
                $role === 'TEACHER' => $this->teacherFacts($user),
                $role === 'EXAM_ADMIN' => $this->examAdminFacts(),
                $role === 'STUDENT' => $this->studentFacts($user),
                $role === 'PARENT' => $this->parentFacts($user),
                $role === 'LIBRARIAN' => $this->librarianFacts(),
                $role === 'NURSE' => $this->nurseFacts(),
                default => [],
            };
        } catch (Throwable $e) {
            // Never break chat because a single aggregate query failed.
            \Illuminate\Support\Facades\Log::warning('RoleScopedFacts::for failed', [
                'role' => $role,
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return ['_error' => 'live data temporarily unavailable'];
        }
    }

    /**
     * Render the facts as a system-prompt-friendly string. Empty if no
     * facts. Always prefixed with a clear marker so the model knows
     * what to trust.
     */
    public function asSystemBlock(array $facts): string
    {
        if (empty($facts)) {
            return '';
        }

        return "\n\n---\nLIVE DATA SNAPSHOT (authoritative — derived from this user's permitted scope. If the user asks for a number you don't see here, say you don't have it):\n"
            . json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\n---\n";
    }

    // ── Role-specific scopes ──────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    private function adminFacts(string $role): array
    {
        $yearId = AcademicYear::query()->where('is_current', true)->value('id');

        $studentTotal = Student::query()->count();
        $studentEnrolled = Student::query()->where('status', 'enrolled')->count();
        $studentPending  = Student::query()->where('status', 'pending')->count();
        $studentByClass = Student::query()
            ->where('status', 'enrolled')
            ->selectRaw('class_id, count(*) as c')
            ->groupBy('class_id')
            ->pluck('c', 'class_id')
            ->all();

        $classNames = SchoolClass::query()->pluck('name', 'id')->all();
        $byClassNamed = [];
        foreach ($studentByClass as $cid => $count) {
            $byClassNamed[$classNames[$cid] ?? $cid] = (int) $count;
        }

        $finance = $this->financeFacts();
        $attendance = $this->attendanceFacts();
        $behaviour = $this->behaviourFacts();

        $tenant = function_exists('tenant') ? tenant() : null;

        return [
            'role' => $role,
            'school' => [
                'name' => $tenant?->school_name ?? config('app.name'),
                'academic_year' => AcademicYear::query()->where('is_current', true)->value('name'),
            ],
            'students' => [
                'total' => $studentTotal,
                'enrolled' => $studentEnrolled,
                'pending_admission' => $studentPending,
                'by_class' => $byClassNamed,
            ],
            'staff' => [
                'total_active_users' => SchoolUser::query()->where('is_active', true)->count(),
            ],
            'finance' => $finance,
            'attendance' => $attendance,
            'behaviour' => $behaviour,
        ];
    }

    /**
     * School-wide attendance facts: 30-day average, today's count, top
     * absent classes. Cheap aggregates only.
     *
     * @return array<string,mixed>
     */
    private function attendanceFacts(): array
    {
        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $rows = AttendanceRecord::query()
            ->where('date', '>=', $thirtyDaysAgo)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();
        $total = array_sum($rows);
        $present = (int) ($rows['present'] ?? 0);
        $absent = (int) ($rows['absent'] ?? 0);
        $late = (int) ($rows['late'] ?? 0);

        $todayAbsent = AttendanceRecord::query()
            ->where('date', now()->toDateString())
            ->where('status', 'absent')
            ->count();

        return [
            'window' => 'last 30 days',
            'total_records' => $total,
            'percent_present' => $total > 0 ? round($present * 100 / $total, 1) : null,
            'percent_absent' => $total > 0 ? round($absent * 100 / $total, 1) : null,
            'percent_late' => $total > 0 ? round($late * 100 / $total, 1) : null,
            'today' => [
                'absent_count' => $todayAbsent,
            ],
        ];
    }

    /**
     * School-wide behaviour facts for the last 30 days: counts by
     * severity. No student names — admins use the dedicated page for
     * per-student detail.
     *
     * @return array<string,mixed>
     */
    private function behaviourFacts(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        $bySeverity = BehaviorIncident::query()
            ->where('incident_date', '>=', $thirtyDaysAgo->toDateString())
            ->selectRaw('severity, count(*) as c')
            ->groupBy('severity')
            ->pluck('c', 'severity')
            ->all();

        return [
            'window' => 'last 30 days',
            'total' => array_sum($bySeverity),
            'by_severity' => $bySeverity,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function financeFacts(): array
    {
        $yearId = AcademicYear::query()->where('is_current', true)->value('id');
        $thisMonth = now()->format('Y-m');

        $base = StudentFee::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId));

        $totalBilledPaisas = (clone $base)->sum('amount_paisas');
        $totalCollectedPaisas = (clone $base)->sum('paid_paisas');
        $totalFinePaisas = (clone $base)->sum('fine_paisas');
        $totalDiscountPaisas = (clone $base)->sum('discount_paisas');
        $outstandingPaisas = $totalBilledPaisas + $totalFinePaisas - $totalCollectedPaisas - $totalDiscountPaisas;

        $unpaidStudents = (clone $base)
            ->whereIn('status', ['pending', 'partial'])
            ->distinct('student_id')
            ->count('student_id');

        $paidStudents = (clone $base)
            ->where('status', 'paid')
            ->distinct('student_id')
            ->count('student_id');

        $thisMonthBase = (clone $base)->where('month', $thisMonth);
        $thisMonthPaid = (clone $thisMonthBase)->where('status', 'paid')->count();
        $thisMonthUnpaid = (clone $thisMonthBase)->whereIn('status', ['pending', 'partial'])->count();

        // Expense uses expense_date (not academic_year_id), so resolve the
        // year window from AcademicYear.start_date instead.
        $yearStart = AcademicYear::query()->where('is_current', true)->value('start_date');
        $expensesYTD = Expense::query()
            ->when($yearStart, fn ($q) => $q->where('expense_date', '>=', $yearStart))
            ->sum('amount_paisas');

        return [
            'currency' => 'PKR',
            'totals' => [
                'billed' => self::pkr($totalBilledPaisas),
                'collected' => self::pkr($totalCollectedPaisas),
                'outstanding' => self::pkr($outstandingPaisas),
                'fines_added' => self::pkr($totalFinePaisas),
                'discounts_given' => self::pkr($totalDiscountPaisas),
                'expenses_year_to_date' => self::pkr($expensesYTD),
                'net_year_to_date' => self::pkr($totalCollectedPaisas - $expensesYTD),
            ],
            'students' => [
                'paid_in_full' => $paidStudents,
                'with_outstanding' => $unpaidStudents,
            ],
            'this_month' => [
                'month' => $thisMonth,
                'paid_invoices' => $thisMonthPaid,
                'unpaid_invoices' => $thisMonthUnpaid,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function teacherFacts(SchoolUser $user): array
    {
        $taughtClassIds = ClassSubject::query()
            ->where('teacher_id', $user->id)
            ->pluck('class_id')
            ->unique()
            ->values()
            ->all();

        $taughtSectionIds = ClassSubject::query()
            ->where('teacher_id', $user->id)
            ->whereNotNull('section_id')
            ->pluck('section_id')
            ->unique()
            ->values()
            ->all();

        if (empty($taughtClassIds)) {
            return ['role' => 'TEACHER', 'note' => 'No classes assigned to you yet.'];
        }

        $classNames = SchoolClass::query()
            ->whereIn('id', $taughtClassIds)
            ->pluck('name', 'id')
            ->all();

        $sectionNames = Section::query()
            ->whereIn('id', $taughtSectionIds)
            ->pluck('name', 'id')
            ->all();

        $studentCount = Student::query()
            ->where('status', 'enrolled')
            ->whereIn('class_id', $taughtClassIds)
            ->count();

        $homeworkOpen = HomeworkAssignment::query()
            ->where('teacher_id', $user->id)
            ->where('due_date', '>=', now()->toDateString())
            ->count();

        return [
            'role' => 'TEACHER',
            'classes_taught' => array_values($classNames),
            'sections_taught' => array_values($sectionNames),
            'enrolled_students_in_my_classes' => $studentCount,
            'open_homework_assignments_set_by_me' => $homeworkOpen,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function examAdminFacts(): array
    {
        return [
            'role' => 'EXAM_ADMIN',
            'students_total' => Student::query()->where('status', 'enrolled')->count(),
            'classes_total' => SchoolClass::query()->count(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function studentFacts(SchoolUser $user): array
    {
        $student = Student::query()
            ->where('school_user_id', $user->id)
            ->with(['schoolClass', 'section', 'academicYear'])
            ->first();

        if (! $student) {
            return ['role' => 'STUDENT', 'note' => 'No student record linked to this user.'];
        }

        $att = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $attTotal = array_sum($att);
        $attPresent = (int) ($att['present'] ?? 0);
        $attendancePct = $attTotal > 0 ? round($attPresent * 100 / $attTotal, 1) : null;

        $fees = StudentFee::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, count(*) as c, sum(amount_paisas - paid_paisas + fine_paisas - discount_paisas) as outstanding')
            ->groupBy('status')
            ->get();

        $outstanding = StudentFee::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'partial'])
            ->selectRaw('coalesce(sum(amount_paisas - paid_paisas + fine_paisas - discount_paisas), 0) as total')
            ->value('total');

        return [
            'role' => 'STUDENT',
            'me' => [
                'name' => $student->full_name,
                'class' => $student->schoolClass?->name,
                'section' => $student->section?->name,
                'admission_number' => $student->admission_number,
                'registration_number' => $student->registration_number,
            ],
            'attendance' => [
                'total_recorded' => $attTotal,
                'percent_present' => $attendancePct,
            ],
            'fees' => [
                'outstanding' => self::pkr($outstanding),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function parentFacts(SchoolUser $user): array
    {
        $children = Student::query()
            ->whereHas('guardians', fn ($q) => $q->where('school_user_id', $user->id))
            ->with(['schoolClass', 'section'])
            ->get();

        if ($children->isEmpty()) {
            return ['role' => 'PARENT', 'note' => 'No children linked to this account.'];
        }

        $rows = [];
        foreach ($children as $child) {
            $outstanding = StudentFee::query()
                ->where('student_id', $child->id)
                ->whereIn('status', ['pending', 'partial'])
                ->selectRaw('coalesce(sum(amount_paisas - paid_paisas + fine_paisas - discount_paisas), 0) as total')
                ->value('total');

            $att = AttendanceRecord::query()
                ->where('student_id', $child->id)
                ->selectRaw('status, count(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status')
                ->all();
            $total = array_sum($att);
            $attendancePct = $total > 0 ? round(((int) ($att['present'] ?? 0)) * 100 / $total, 1) : null;

            $rows[] = [
                'name' => $child->full_name,
                'class' => $child->schoolClass?->name,
                'section' => $child->section?->name,
                'attendance_percent' => $attendancePct,
                'fees_outstanding' => self::pkr($outstanding),
            ];
        }

        return ['role' => 'PARENT', 'children' => $rows];
    }

    /**
     * @return array<string,mixed>
     */
    private function librarianFacts(): array
    {
        return ['role' => 'LIBRARIAN'];
    }

    /**
     * @return array<string,mixed>
     */
    private function nurseFacts(): array
    {
        return ['role' => 'NURSE'];
    }

    /**
     * Postgres aggregate functions (sum/count) return numeric BIGINTs as
     * strings via the PDO driver, so accept anything Stringable here and
     * coerce inside.
     */
    private static function pkr(mixed $paisas): string
    {
        return 'PKR ' . number_format(((int) ($paisas ?? 0)) / 100, 2);
    }
}
