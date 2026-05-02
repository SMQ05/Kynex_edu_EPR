<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * ActionType — All sensitive action types that go through the approval workflow.
 *
 * Grouped by category for readability.
 * Approver is determined by the action category and escalation level.
 */
enum ActionType: string
{
    // ── Student-Related ────────────────────────────────────────────
    case DeleteStudent            = 'delete_student';
    case DismissStudent           = 'dismiss_student';
    case ExpelStudent             = 'expel_student';
    case BulkImportStudents       = 'bulk_import_students';
    case TransferStudent          = 'transfer_student';
    case RollbackPromotion        = 'rollback_student_promotion';

    // ── Academic / Exam-Related ────────────────────────────────────
    case EditPublishedMarks       = 'edit_published_marks';
    case DeleteExamMarks          = 'delete_exam_marks';
    case OverrideFinalGrade       = 'override_final_grade';
    case ModifyPublishedResults   = 'modify_published_results';
    case ChangeAcademicYearMidway = 'change_academic_year_midway';

    // ── Finance-Related ────────────────────────────────────────────
    case IssueRefund              = 'issue_refund';            // above threshold
    case VoidFeePayment           = 'void_fee_payment';
    case ApproveLargeExpense      = 'approve_large_expense';
    case ApprovePayrollRun        = 'approve_payroll_run';
    case ModifyFeeStructureMidTerm = 'modify_fee_structure_mid_term';
    case ApproveScholarship       = 'approve_scholarship';     // above threshold
    case WriteOffDebt             = 'write_off_debt';

    // ── Staff / HR-Related ─────────────────────────────────────────
    case DeleteStaff              = 'delete_staff';
    case FireStaff                = 'fire_staff';
    case SuspendStaff             = 'suspend_staff';
    case AssignSensitiveRole      = 'assign_sensitive_role';
    case RemoveSensitiveRole      = 'remove_sensitive_role';
    case BulkImportStaff          = 'bulk_import_staff';

    // ── Role Succession & Handover ────────────────────────────────
    case RoleSuccession           = 'role_succession';         // Institute Head approves
    case SchoolAdminAssignment    = 'school_admin_assignment'; // Institute Head self-approves
    case InstituteHeadAssignment  = 'institute_head_assignment'; // SaaS Super Admin approves

    // ── System & Security-Related ──────────────────────────────────
    case CreateSensitiveCustomRole = 'create_sensitive_custom_role';
    case GrantConfidentialAccess  = 'grant_confidential_access';
    case DisableBiometric         = 'disable_biometric';
    case ClearAuditLogs           = 'clear_audit_logs';
    case DeleteSystemCertificate  = 'delete_system_certificate';
    case ChangeSchoolBranding     = 'change_school_branding';

    // ── Helpers ────────────────────────────────────────────────────

    /**
     * Returns the approver level required for this action type.
     * 'institute_head' = Institute Head / Multi-IH for their schools
     * 'saas_admin'     = SaaS Super Admin only
     */
    public function approverLevel(): string
    {
        return match ($this) {
            self::InstituteHeadAssignment => 'saas_admin',
            default                       => 'institute_head',
        };
    }

    /**
     * Human-readable label for UI display.
     */
    public function label(): string
    {
        return match ($this) {
            self::DeleteStudent             => 'Delete Student Record',
            self::DismissStudent            => 'Dismiss Student',
            self::ExpelStudent              => 'Expel Student',
            self::BulkImportStudents        => 'Bulk Import Students (>50)',
            self::TransferStudent           => 'Transfer Student',
            self::RollbackPromotion         => 'Rollback Student Promotion',
            self::EditPublishedMarks        => 'Edit Published Marks',
            self::DeleteExamMarks           => 'Delete Exam Marks',
            self::OverrideFinalGrade        => 'Override Final Grade',
            self::ModifyPublishedResults    => 'Modify Published Results',
            self::ChangeAcademicYearMidway  => 'Change Academic Year Mid-Year',
            self::IssueRefund               => 'Issue Refund (Above Threshold)',
            self::VoidFeePayment            => 'Void Fee Payment',
            self::ApproveLargeExpense       => 'Approve Large Expense',
            self::ApprovePayrollRun         => 'Approve Payroll Run',
            self::ModifyFeeStructureMidTerm => 'Modify Fee Structure Mid-Term',
            self::ApproveScholarship        => 'Approve Scholarship (Above Threshold)',
            self::WriteOffDebt              => 'Write Off Debt/Balance',
            self::DeleteStaff               => 'Delete Staff Record',
            self::FireStaff                 => 'Fire / Terminate Staff',
            self::SuspendStaff              => 'Suspend Staff',
            self::AssignSensitiveRole       => 'Assign Sensitive Role',
            self::RemoveSensitiveRole       => 'Remove Sensitive Role',
            self::BulkImportStaff           => 'Bulk Import Staff',
            self::RoleSuccession            => 'Role Succession / Handover',
            self::SchoolAdminAssignment     => 'Assign New School Admin',
            self::InstituteHeadAssignment   => 'Assign New Institute Head',
            self::CreateSensitiveCustomRole => 'Create Custom Role with Finance/HR Permissions',
            self::GrantConfidentialAccess   => 'Grant Confidential Data Access',
            self::DisableBiometric          => 'Disable Biometric Attendance',
            self::ClearAuditLogs            => 'Clear / Reset Audit Logs',
            self::DeleteSystemCertificate   => 'Delete System-Generated Certificate',
            self::ChangeSchoolBranding      => 'Change School Branding / Identity',
        };
    }
}
