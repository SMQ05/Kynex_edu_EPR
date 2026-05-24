<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use Filament\Pages\Page;

/**
 * In-app help center. Explains every navigation section in plain words
 * so admins, institute heads, and teachers can self-serve. The page is
 * intentionally a static reference — no DB queries, no live data —
 * so it always loads even when the rest of the app is misbehaving.
 *
 * Visible to anyone who is logged in.
 */
class HelpCenter extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Help & Support';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'How to use';

    protected string $view = 'filament.school-admin.pages.help-center';

    public function getTitle(): string
    {
        return 'How to use KynexEdu';
    }

    public function getSubheading(): ?string
    {
        return 'A short explanation of every section in the system. Click a topic to expand.';
    }

    /** Visible to anyone authenticated; the page itself is read-only and contains no data. */
    public static function canAccess(): bool
    {
        return auth('school_users')->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth('school_users')->check();
    }

    /**
     * Topics shown on the help page, grouped by area.
     *
     * @return array<int,array{title:string,icon:string,items:array<int,array{q:string,a:string}>}>
     */
    public function topics(): array
    {
        return [
            [
                'title' => 'Fees',
                'icon'  => 'heroicon-o-banknotes',
                'items' => [
                    ['q' => 'What is a Fee Group?',
                     'a' => 'A bucket for related fees, e.g. "Tuition", "Transport", "Boarding", "Examination". Each group can hold many fee types.'],
                    ['q' => 'What is a Fee Type?',
                     'a' => 'A specific charge in a Fee Group, e.g. "Monthly Tuition" or "Admission Fee". Mark it as recurring for monthly bills, leave un-checked for one-time charges.'],
                    ['q' => 'What is the Fee Structure?',
                     'a' => 'The price book. One row per Class × Fee Type × Academic Year, with the amount in PKR and the due day. Generate Fees reads from these rows.'],
                    ['q' => 'How do I generate fees for a month?',
                     'a' => 'Open Fees → Generate Fees → "Roll out monthly fees". Pick a class, optionally a section, the academic year, and the month (e.g. 2026-05). Re-running for the same month is safe — duplicates are skipped.'],
                    ['q' => 'How do I add a one-time charge for one student?',
                     'a' => 'Generate Fees → "One-time fee". Pick the student, the fee type (must be marked one-time), the amount and due date.'],
                    ['q' => 'How do I apply late fees?',
                     'a' => 'Generate Fees → "Apply late fees". Set the per-day fine and an optional cap; the system fines every overdue invoice.'],
                    ['q' => 'Where do I see who has not paid?',
                     'a' => 'Fees → Defaulters. Each row is an overdue invoice. Select rows and click "Send reminder" to dispatch a WhatsApp message (with SMS fallback) to each guardian.'],
                ],
            ],
            [
                'title' => 'Finance',
                'icon'  => 'heroicon-o-chart-bar',
                'items' => [
                    ['q' => 'What is a Budget?',
                     'a' => 'A planned spending cap by category for a period (e.g. PKR 200,000 for "Salaries" in 2026). The system warns when expenses exceed budgets.'],
                    ['q' => 'What is an Expense?',
                     'a' => 'Money the school spends — utilities, supplies, salaries, repairs. Each expense belongs to an Expense Category.'],
                    ['q' => 'Why are Fees and Finance separate?',
                     'a' => 'Fees is income from students. Finance is outgoing money (expenses) and budgeting. Keeping them separate makes month-end reconciliation easier.'],
                ],
            ],
            [
                'title' => 'Students & Admissions',
                'icon'  => 'heroicon-o-academic-cap',
                'items' => [
                    ['q' => 'How do I admit a new student?',
                     'a' => 'Students → Create. The form asks for personal info, class, section, and guardian. New admissions go to the Approval Queue (institute head approves) before becoming "enrolled".'],
                    ['q' => 'How do I see a student\'s 360° profile?',
                     'a' => 'Open the Students list, click any row, then click the "Profile" action. It shows demographics, attendance, behaviour, exam marks, certificates, and fee status all in one place.'],
                    ['q' => 'What is the Registration Number vs Admission Number?',
                     'a' => 'Admission Number is assigned only when the student is admitted. Registration Number is auto-generated for tracking — both are visible on certificates and ID cards.'],
                ],
            ],
            [
                'title' => 'Examinations',
                'icon'  => 'heroicon-o-clipboard-document-check',
                'items' => [
                    ['q' => 'Who can enter marks?',
                     'a' => 'Teachers and Exam Admins. School admins and institute heads see reports but don\'t enter marks. Marks Entry is hidden from the admin sidebar by design.'],
                    ['q' => 'What is the difference between Homework and an Exam?',
                     'a' => 'Homework / Class Assignment / Class Test marks live alongside Exam marks. Each can carry a custom weightage that contributes to the final grade.'],
                    ['q' => 'How does grading weightage work?',
                     'a' => 'Grading Weights (Examinations group) sets the percentage split — e.g. 60% Final + 20% Mid + 10% Homework + 10% Class Tests = 100%. The Annual Result is computed from these weights.'],
                ],
            ],
            [
                'title' => 'Staff & HR',
                'icon'  => 'heroicon-o-user-group',
                'items' => [
                    ['q' => 'How do I hire a staff member?',
                     'a' => 'School Users → Create, fill the role + campus + department, and submit. Staff hires are routed to the Approval Queue. Once approved, the user receives a "Set Password" email.'],
                    ['q' => 'What is a Department vs a Designation?',
                     'a' => 'A Department is a unit (e.g. Mathematics, Administration). A Designation is the job title (e.g. Senior Teacher, Vice Principal). One staff member belongs to one department and one designation.'],
                    ['q' => 'How do I revoke staff access without deleting?',
                     'a' => 'Open the staff member, toggle "Active" off. Soft-suspends the user immediately; routes through Approval Queue if you\'re a school admin.'],
                ],
            ],
            [
                'title' => 'Approvals',
                'icon'  => 'heroicon-o-check-badge',
                'items' => [
                    ['q' => 'What goes through the Approval Queue?',
                     'a' => 'Anything that materially changes data: new student admission, staff hire/suspend, marks edits after submission, refunds, bulk fee waivers. Institute heads review and approve.'],
                    ['q' => 'Can I approve everything at once?',
                     'a' => 'Yes — at the top of the Approval Queue use "Approve All Pending" or "Reject All Pending" with a single bulk note.'],
                ],
            ],
            [
                'title' => 'AI Assistant',
                'icon'  => 'heroicon-o-sparkles',
                'items' => [
                    ['q' => 'What does the AI know?',
                     'a' => 'Anything you have permission to see. Admins/institute heads can ask "how many students do we have?" or "what is our outstanding fee total?". Teachers can ask about their classes only. Parents see only their children\'s data. The AI never reveals data outside the user\'s role.'],
                    ['q' => 'Can the AI change data?',
                     'a' => 'No. It only reads and explains. To change data, use the actual page (Students, Fees, Marks Entry, etc.).'],
                ],
            ],
            [
                'title' => 'System & Roles',
                'icon'  => 'heroicon-o-shield-check',
                'items' => [
                    ['q' => 'What is the role hierarchy?',
                     'a' => 'Multi-Institute Head > Institute Head > School Admin > Teachers / Exam Admin / Registrar / HR / etc. > Students > Parents. Institute Heads and Multi-IHs are managed only by Kynex Solutions (SaaS admin).'],
                    ['q' => 'How do I change a user\'s permissions?',
                     'a' => 'System → Role Management. School admins can edit roles below their level only. Institute Head and Multi-IH roles are SaaS-only and not editable from the school panel.'],
                    ['q' => 'Why can\'t I add a campus?',
                     'a' => 'Only Multi-Institute Head and Kynex Solutions (SaaS admin) can add campuses. Email hello@kynexsolutions.com to request one.'],
                ],
            ],
        ];
    }
}
