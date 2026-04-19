<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * TenantDefaultRolesSeeder — Seeds system roles and permissions for
 * every new tenant database.
 *
 * System roles (is_system = true) cannot be deleted by users.
 * Permissions are prefixed by module for clarity.
 */
class TenantDefaultRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Prevent re-running in central DB
        if (! tenancy()->initialized) {
            return;
        }

        $this->seedRoles();
        $this->seedPermissions();
        $this->assignPermissionsToRoles();

        // Flush permission cache
        app()['cache']->forget('spatie.permission.cache');
    }

    private function seedRoles(): void
    {
        $roles = [
            // ── SaaS-assigned ownership roles ──────────────────────────
            // These are set exclusively by the KynexEdu platform admin.
            // No school-level user may assign or revoke them.
            [
                'name'       => 'MULTI_INSTITUTE_HEAD',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'INSTITUTE_HEAD',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            // ── School-level roles ──────────────────────────────────────
            [
                'name'       => 'SCHOOL_ADMIN',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'TEACHER',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'PARENT',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'STUDENT',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'ACCOUNTANT',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'LIBRARIAN',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'RECEPTIONIST',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'TRANSPORT_MANAGER',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'HOSTEL_WARDEN',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'NURSE',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'COUNSELOR',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'CAFETERIA_MANAGER',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'ATTENDANCE_CLERK',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],

            // ── Phase 10.1: Additional system roles ────────────────────
            [
                'name'       => 'REGISTRAR',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'BURSAR',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'HR_MANAGER',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
            [
                'name'       => 'EXAM_ADMIN',
                'guard_name' => 'school_users',
                'is_system'  => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']],
                $role,
            );
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            // ── Dashboard ─────────────────────────────────────────────
            'dashboard.view',

            // ── Academic Setup ────────────────────────────────────────
            'academic_year.view',
            'academic_year.create',
            'academic_year.update',
            'academic_year.delete',
            'academic_year.set_current',
            'class.view',
            'class.create',
            'class.update',
            'class.delete',
            'section.view',
            'section.create',
            'section.update',
            'section.delete',
            'subject.view',
            'subject.create',
            'subject.update',
            'subject.delete',
            'class_routine.view',
            'class_routine.create',
            'class_routine.update',
            'class_routine.delete',
            'class_routine.print',

            // ── Students ──────────────────────────────────────────────
            'student.view',
            'student.create',
            'student.update',
            'student.delete',
            'student.change_status',
            'student.promote',
            'student.bulk_promote',
            'student.import',
            'student.export',
            'student.print_id_card',
            'student.download_pdf',
            'student_category.view',
            'student_category.create',
            'student_category.update',
            'student_category.delete',

            // ── Staff & HR ─────────────────────────────────────────────
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',
            'staff.payroll',
            'staff.leave_manage',
            'department.view',
            'department.create',
            'department.update',
            'department.delete',
            'designation.view',
            'designation.create',
            'designation.update',
            'designation.delete',
            'payroll.view',
            'payroll.create',
            'payroll.process',
            'payroll.approve',
            'leave_request.view',
            'leave_request.create',
            'leave_request.approve',

            // ── Fees & Finance ────────────────────────────────────────
            'fee.view',
            'fee.create',
            'fee.collect',
            'fee.waive',
            'fee.report',
            'fee_group.view',
            'fee_group.create',
            'fee_group.update',
            'fee_group.delete',
            'fee_type.view',
            'fee_type.create',
            'fee_type.update',
            'fee_type.delete',
            'expense.view',
            'expense.create',
            'expense.approve',
            'expense.delete',
            'budget.view',
            'budget.create',
            'budget.update',
            'budget.delete',

            // ── Communication ─────────────────────────────────────────
            'communication.view',
            'communication.send_sms',
            'communication.send_whatsapp',
            'communication.send_email',
            'communication.template.manage',

            // ── Library ───────────────────────────────────────────────
            'library.view',
            'library.borrow',
            'library.return',
            'library.manage_books',

            // ── Transport ─────────────────────────────────────────────
            'transport.view',
            'transport.manage_routes',
            'transport.assign_student',

            // ── Hostel ───────────────────────────────────────────────
            'hostel.view',
            'hostel.manage_rooms',
            'hostel.assign_student',

            // ── Inventory ────────────────────────────────────────────
            'inventory.view',
            'inventory.manage',

            // ── Online Classes ───────────────────────────────────────
            'online_class.view',
            'online_class.create',
            'online_class.update',
            'online_class.cancel',
            'online_class.manage_attendance',

            // ── Attendance ──────────────────────────────────────────
            'attendance.view',
            'attendance.mark',

            // ── Visitor ─────────────────────────────────────────────
            'visitor.view',
            'visitor.manage',

            // ── Health / Nurse ──────────────────────────────────────
            'health.view',
            'health.manage',

            // ── Counselor ───────────────────────────────────────────
            'counselor.view',
            'counselor.manage',

            // ── Cafeteria ───────────────────────────────────────────
            'cafeteria.view',
            'cafeteria.manage',

            // ── Gate Pass ───────────────────────────────────────────
            'gate_pass.view',
            'gate_pass.manage',

            // ── Reports ───────────────────────────────────────────────
            'report.view',
            'report.student',
            'report.staff',
            'report.fee',
            'report.attendance',
            'report.exam',

            // ── System ───────────────────────────────────────────────
            'system.user_manage',
            'system.role_manage',
            'system.settings',
            'system.audit_log',
            'system.backup',

            // ── Approval Workflow (Phase 10.1) ──────────────────────
            'bypass_approvals',
            'view_all_approvals',
            'approve_requests',
            'reject_requests',
            'impersonate_school_users',
            'manage_school_settings',
            'view_all_reports',
            'manage_roles',
            'manage_staff',
            'manage_students',
            'manage_fees',
            'manage_exams',
            'manage_library',
            'manage_transport',
            'manage_hostel',
            'manage_inventory',
            'manage_cafeteria',
            'manage_online_classes',
            'manage_notices',
            'manage_expenses',
            'view_health_records',
            'manage_campus',

            // ── Registrar (Phase 10.1) ──────────────────────────────
            'manage_admissions',
            'manage_documents',
            'view_academic_records',
            'generate_certificates',
            'generate_id_cards',
            'manage_promotions',

            // ── Bursar (Phase 10.1) ─────────────────────────────────
            'collect_payments',
            'generate_receipts',
            'view_financial_reports',
            'request_refund',
            'manage_fee_structure',

            // ── HR Manager (Phase 10.1) ─────────────────────────────
            'manage_payroll',
            'manage_leaves',
            'manage_attendance',
            'view_hr_reports',
            'manage_salary_components',

            // ── Exam Admin (Phase 10.1) ─────────────────────────────
            'enter_marks',
            'publish_results',
            'generate_report_cards',
            'generate_admit_cards',
            'manage_grade_rules',
            'view_exam_reports',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'school_users'],
            );
        }
    }

    private function assignPermissionsToRoles(): void
    {
        // MULTI_INSTITUTE_HEAD: same as INSTITUTE_HEAD — set by SaaS admin only
        // Receives all permissions so they can view/oversee everything across institutes.
        $multiInstituteHead = Role::findByName('MULTI_INSTITUTE_HEAD');
        $multiInstituteHead->syncPermissions(Permission::where('guard_name', 'school_users')->get());

        // SCHOOL_ADMIN: full access
        $schoolAdmin = Role::findByName('SCHOOL_ADMIN');
        $schoolAdmin->syncPermissions(Permission::where('guard_name', 'school_users')->get());

        // INSTITUTE_HEAD: principal / head of institute — SaaS-assigned.
        // Receives all permissions (consolidates the former INSTITUTE_OWNER and CAMPUS_OWNER roles).
        $instituteHead = Role::findByName('INSTITUTE_HEAD');
        $instituteHead->syncPermissions(Permission::where('guard_name', 'school_users')->get());

        // TEACHER
        $teacher = Role::findByName('TEACHER');
        $teacher->syncPermissions([
            'dashboard.view',
            'class_routine.view',
            'student.view',
            'online_class.view', 'online_class.create',
            'report.view',
            'leave_request.view', 'leave_request.create',
        ]);

        // PARENT
        $parent = Role::findByName('PARENT');
        $parent->syncPermissions([
            'dashboard.view',
            'student.view',  // own children
            'fee.view',      // view feeChallan
            'report.view',   // child's results
            'leave_request.view', 'leave_request.create',
        ]);

        // STUDENT
        $student = Role::findByName('STUDENT');
        $student->syncPermissions([
            'dashboard.view',
            'online_class.view',
            'report.view',
            'leave_request.view', 'leave_request.create',
        ]);

        // ACCOUNTANT
        $accountant = Role::findByName('ACCOUNTANT');
        $accountant->syncPermissions([
            'dashboard.view',
            'fee.view', 'fee.create', 'fee.collect', 'fee.waive', 'fee.report',
            'fee_group.view', 'fee_group.create', 'fee_group.update',
            'fee_type.view', 'fee_type.create', 'fee_type.update',
            'expense.view', 'expense.create', 'budget.view', 'budget.create',
            'staff.payroll', 'payroll.view', 'payroll.create',
            'report.fee',
            'student.view',
        ]);

        // LIBRARIAN
        $librarian = Role::findByName('LIBRARIAN');
        $librarian->syncPermissions([
            'dashboard.view',
            'library.view', 'library.borrow', 'library.return', 'library.manage_books',
        ]);

        // RECEPTIONIST
        $receptionist = Role::findByName('RECEPTIONIST');
        $receptionist->syncPermissions([
            'dashboard.view',
            'student.view', 'student.create',
            'staff.view',
            'visitor.view', 'visitor.manage',
            'communication.view', 'communication.send_sms', 'communication.send_whatsapp',
        ]);

        // TRANSPORT_MANAGER
        $transportManager = Role::findByName('TRANSPORT_MANAGER');
        $transportManager->syncPermissions([
            'dashboard.view',
            'transport.view', 'transport.manage_routes', 'transport.assign_student',
            'student.view',
            'report.view',
        ]);

        // HOSTEL_WARDEN
        $hostelWarden = Role::findByName('HOSTEL_WARDEN');
        $hostelWarden->syncPermissions([
            'dashboard.view',
            'hostel.view', 'hostel.manage_rooms', 'hostel.assign_student',
            'gate_pass.view', 'gate_pass.manage',
            'student.view',
            'report.view',
        ]);

        // NURSE
        $nurse = Role::findByName('NURSE');
        $nurse->syncPermissions([
            'dashboard.view',
            'health.view', 'health.manage',
            'student.view',
        ]);

        // COUNSELOR
        $counselor = Role::findByName('COUNSELOR');
        $counselor->syncPermissions([
            'dashboard.view',
            'counselor.view', 'counselor.manage',
            'student.view',
        ]);

        // CAFETERIA_MANAGER
        $cafeteriaManager = Role::findByName('CAFETERIA_MANAGER');
        $cafeteriaManager->syncPermissions([
            'dashboard.view',
            'cafeteria.view', 'cafeteria.manage',
            'inventory.view',
        ]);

        // ATTENDANCE_CLERK
        $attendanceClerk = Role::findByName('ATTENDANCE_CLERK');
        $attendanceClerk->syncPermissions([
            'dashboard.view',
            'attendance.view', 'attendance.mark',
            'student.view',
            'class_routine.view',
            'class.view',
            'communication.send_sms', 'communication.send_whatsapp',
            'report.view', 'report.attendance',
        ]);

        // ── Phase 10.1: New role permission assignments ──────────────

        // REGISTRAR: student admissions, documents, certificates
        $registrar = Role::findByName('REGISTRAR');
        $registrar->syncPermissions([
            'dashboard.view',
            'manage_students',
            'student.view',
            'manage_admissions',
            'manage_documents',
            'view_academic_records',
            'generate_certificates',
            'generate_id_cards',
            'manage_promotions',
            'report.view',
        ]);

        // BURSAR: fees, payments, financial reporting
        $bursar = Role::findByName('BURSAR');
        $bursar->syncPermissions([
            'dashboard.view',
            'manage_fees',
            'fee.view',
            'collect_payments',
            'generate_receipts',
            'view_financial_reports',
            'request_refund',
            'manage_fee_structure',
            'expense.view',
        ]);

        // HR_MANAGER: staff, payroll, leaves
        $hrManager = Role::findByName('HR_MANAGER');
        $hrManager->syncPermissions([
            'dashboard.view',
            'manage_staff',
            'staff.view',
            'manage_payroll',
            'manage_leaves',
            'manage_attendance',
            'view_hr_reports',
            'manage_salary_components',
        ]);

        // EXAM_ADMIN: exams, marks, results, report cards
        $examAdmin = Role::findByName('EXAM_ADMIN');
        $examAdmin->syncPermissions([
            'dashboard.view',
            'manage_exams',
            'report.exam',
            'enter_marks',
            'publish_results',
            'generate_report_cards',
            'generate_admit_cards',
            'manage_grade_rules',
            'view_exam_reports',
        ]);
    }
}
