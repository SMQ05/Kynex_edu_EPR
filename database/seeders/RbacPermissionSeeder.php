<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RbacPermissionSeeder — Seeds all KynexEdu ERP roles and permissions.
 *
 * Run this seeder INSIDE a tenant context (it writes to the tenant DB).
 * The 'guard_name' => 'school_users' matches SchoolUser's $guard_name.
 *
 * Usage:
 *   tenancy()->initialize($tenant);
 *   Artisan::call('db:seed', ['--class' => 'RbacPermissionSeeder']);
 *   tenancy()->end();
 *
 * Or via RbacPermissionSeeder::runForTenant($tenant).
 */
class RbacPermissionSeeder extends Seeder
{
    private const GUARD = 'school_users';

    /**
     * All permission definitions, grouped by category.
     * Format: 'permission_slug' => 'Human Readable Label'
     */
    private function permissions(): array
    {
        return [

            // ════════════════════════════════════════════════════════════
            // STUDENT MANAGEMENT
            // ════════════════════════════════════════════════════════════
            'view_students'                  => 'View Students',
            'create_student'                 => 'Create Student',
            'edit_student'                   => 'Edit Student',
            'delete_student'                 => 'Delete Student (Approval Required)',
            'view_own_student_profile'       => 'View Own Student Profile',
            'view_all_student_profiles'      => 'View All Student Profiles',
            'view_student_documents'         => 'View Student Documents',
            'upload_student_documents'       => 'Upload Student Documents',
            'change_student_status'          => 'Change Student Status (Approval for Critical)',
            'manage_student_admissions'      => 'Manage Student Admissions',
            'enter_admission_marks'          => 'Enter Admission Test / Interview Marks',
            'conduct_admission_interview'    => 'Conduct & Schedule Admission Interviews',
            'create_admission_tests'         => 'Create Admission Tests & Question Banks',
            'manage_exam_attendance'         => 'Mark Exam Attendance & Start Admission Exams',
            'manage_student_transfers'       => 'Manage Student Transfers (Approval Required)',
            'manage_student_promotions'      => 'Manage Student Promotions (Approval Required)',
            'archive_student_records'        => 'Archive Student Records',
            'issue_certificates'             => 'Issue Student Certificates',
            'view_student_basic_info'        => 'View Student Basic Info (Name, Class)',

            // ════════════════════════════════════════════════════════════
            // ACADEMIC MANAGEMENT
            // ════════════════════════════════════════════════════════════
            'manage_classes'                 => 'Manage Classes',
            'manage_sections'                => 'Manage Sections',
            'manage_subjects'                => 'Manage Subjects',
            'manage_timetable'               => 'Manage Timetable',
            'manage_academic_calendar'       => 'Manage Academic Calendar',
            'view_academic_calendar'         => 'View Academic Calendar',
            'assign_teachers_to_subjects'    => 'Assign Teachers to Subjects',
            'manage_homework'                => 'Manage Homework (Own Classes)',
            'manage_assignments'             => 'Manage Assignments (Own Classes)',
            'submit_homework'                => 'Submit Homework (Student)',
            'submit_assignment'              => 'Submit Assignment (Student)',
            'view_own_homework'              => 'View Own Homework',
            'view_own_assignments'           => 'View Own Assignments',
            'view_own_timetable'             => 'View Own Timetable',
            'view_own_attendance'            => 'View Own Attendance',
            'view_own_daily_activity_scores' => 'View Own Daily Activity Scores',
            'enter_daily_activity_scores'    => 'Enter Daily Activity Scores (0–10)',
            'manage_daily_activity_scores'   => 'Manage All Daily Activity Scores',
            'view_classes'                   => 'View Classes',
            'view_sections'                  => 'View Sections',

            // Attendance
            'mark_attendance_manual'         => 'Mark Attendance (Manual — Always Available)',
            'mark_attendance_biometric'      => 'Mark Attendance (Biometric Device)',
            'mark_attendance_fallback'       => 'Mark Attendance (Manual Fallback when Biometric Offline)',
            'view_attendance'                => 'View Attendance Records',
            'edit_attendance'                => 'Edit Attendance (Same-Day Corrections)',
            'process_biometric_data'         => 'Process & Sync Biometric Device Data',
            'generate_attendance_report'     => 'Generate Attendance Report',

            // ════════════════════════════════════════════════════════════
            // EXAMINATION MANAGEMENT
            // ════════════════════════════════════════════════════════════
            'manage_exam_schedules'          => 'Manage Exam Schedules',
            'manage_exam_types'              => 'Manage Exam Types',
            'manage_grading_scales'          => 'Manage Grading Scales',
            'enter_marks'                    => 'Enter Marks (Own Subjects)',
            'edit_marks'                     => 'Edit Marks (Before Deadline)',
            'edit_marks_published'           => 'Edit Published Marks (Approval Required)',
            'delete_marks'                   => 'Delete Marks (Approval Required)',
            'approve_mark_modifications'     => 'Approve Mark Modifications',
            'publish_results'                => 'Publish Exam Results',
            'generate_report_cards'          => 'Generate Report Cards',
            'view_marks'                     => 'View Marks',
            'view_own_marks'                 => 'View Own Marks (Student)',
            'view_own_report_card'           => 'View Own Report Card (Student)',
            'view_own_exam_schedule'         => 'View Own Exam Schedule (Student)',

            // ════════════════════════════════════════════════════════════
            // FEE & FINANCE MANAGEMENT
            // ════════════════════════════════════════════════════════════
            'view_fee_structures'            => 'View Fee Structures',
            'create_fee_structure'           => 'Create Fee Structure',
            'edit_fee_structure'             => 'Edit Fee Structure',
            'delete_fee_structure'           => 'Delete Fee Structure',
            'view_fee_payments'              => 'View Fee Payments',
            'record_fee_payment'             => 'Record Fee Payment',
            'edit_fee_payment'               => 'Edit Fee Payment',
            'void_fee_payment'               => 'Void Fee Payment (Approval Required)',
            'approve_refunds'                => 'Approve Refunds',
            'issue_refunds'                  => 'Issue Refunds (Above Threshold: Approval Required)',
            'manage_expenses'                => 'Manage Expenses',
            'manage_accounts'                => 'Manage Accounts (income, bank, chart, transfers)',
            'manage_wallet'                  => 'Manage Wallets (deposits, refunds, ledger)',
            // Examination (Phase 4)
            'manage_question_bank'           => 'Manage Question Bank',
            'manage_online_exams'            => 'Manage Online Exams',
            'manage_exam_plan'               => 'Manage Exam Plan (admit card, seat plan)',
            'manage_exam_settings'           => 'Manage Exam Settings',
            // Academic content & HR (Phase 6)
            'manage_lesson_plans'            => 'Manage Lesson Plans',
            'manage_study_materials'         => 'Manage Study Materials',
            'manage_download_center'         => 'Manage Download Center',
            'manage_teacher_evaluations'     => 'Manage Teacher Evaluations',
            // Communication & Access (Phase 7)
            'manage_events'                  => 'Manage Events & Calendar',
            'use_chat'                       => 'Use Chat',
            'view_user_logs'                 => 'View User Activity Logs',
            'manage_login_permissions'       => 'Manage Login Permissions',
            'manage_appearance_settings'     => 'Manage Appearance / Theme',
            'manage_system_utilities'        => 'Manage System Utilities',
            'manage_modules'                 => 'Manage Module Toggles',
            // Settings (Phase 8)
            'manage_custom_fields'           => 'Manage Custom Fields',
            'approve_expenses'               => 'Approve Expenses (Below Budget Limit)',
            'approve_large_expenses'         => 'Approve Large Expenses (Approval Required)',
            'manage_budget'                  => 'Manage Budget',
            'view_budget'                    => 'View Budget',
            'manage_payroll'                 => 'Manage Payroll',
            'run_payroll'                    => 'Run Payroll (Approval Required)',
            'view_payroll'                   => 'View Payroll',
            'manage_payroll_inputs'          => 'Manage Payroll Inputs (HR)',
            'manage_scholarships'            => 'Manage Scholarships',
            'approve_scholarships'           => 'Approve Scholarships (Above Threshold: Approval Required)',
            'apply_scholarship_discount'     => 'Apply Scholarship Discount',
            'manage_invoices'                => 'Manage Invoices',
            'generate_fee_receipts'          => 'Generate Fee Receipts',
            'manage_accounts_receivable'     => 'Manage Accounts Receivable',
            'manage_accounts_payable'        => 'Manage Accounts Payable',
            'reconcile_accounts'             => 'Reconcile Accounts',
            'generate_balance_sheet'         => 'Generate Balance Sheet',
            'view_own_fee_statement'         => 'View Own Fee Statement (Student)',
            'view_own_receipts'              => 'View Own Receipts (Student)',
            'view_child_fee_statement'       => 'View Child Fee Statement (Parent)',
            'pay_fee_online'                 => 'Pay Fee Online (Parent/Student)',
            'send_fee_reminders'             => 'Send Fee Reminders',
            'write_off_debt'                 => 'Write Off Debt/Balance (Approval Required)',
            'view_financial_reports'         => 'View Financial Reports',
            'export_financial_reports'       => 'Export Financial Reports',

            // ════════════════════════════════════════════════════════════
            // STAFF & HR MANAGEMENT
            // ════════════════════════════════════════════════════════════
            'view_staff'                     => 'View Staff',
            'create_staff'                   => 'Create Staff',
            'edit_staff'                     => 'Edit Staff',
            'delete_staff'                   => 'Delete Staff (Approval Required)',
            'change_staff_status'            => 'Change Staff Status (Approval for Critical)',
            'manage_staff_documents'         => 'Manage Staff Documents',
            'manage_certifications'          => 'Manage Staff Certifications',
            'manage_staff_attendance'        => 'Manage Staff Attendance',
            'manage_contracts'               => 'Manage Staff Contracts',
            'manage_staff_onboarding'        => 'Manage Staff Onboarding',
            'manage_staff_offboarding'       => 'Manage Staff Offboarding',
            'manage_performance_reviews'     => 'Manage Performance Reviews',
            'manage_leave_requests'          => 'Manage Leave Requests',
            'approve_leave'                  => 'Approve Leave Requests',
            'view_leave_balance'             => 'View Leave Balance',
            'view_payroll_records'           => 'View Payroll Records',

            // Role assignment (stacking)
            'assign_additional_role'         => 'Assign Additional/Secondary Role to Staff or Teacher',
            'assign_sensitive_role'          => 'Assign Sensitive Role (Approval Required)',
            'remove_role_from_staff'         => 'Remove Role from Staff (Approval for Sensitive)',
            'initiate_role_succession'       => 'Initiate Role Succession/Handover (Approval Required)',

            // ════════════════════════════════════════════════════════════
            // COMMUNICATION & NOTIFICATIONS
            // ════════════════════════════════════════════════════════════
            'send_notifications_all'         => 'Send Notifications to All (Students, Parents, Staff)',
            'send_notifications_to_class'    => 'Send Notifications to Own Class/Students',
            'send_notifications_to_staff'    => 'Send Notifications to Staff',
            'send_sms'                       => 'Send SMS',
            'send_email'                     => 'Send Email',
            'send_push_notifications'        => 'Send Push Notifications',
            'manage_announcement_board'      => 'Manage Announcement Board',
            'manage_school_communication_settings' => 'Manage School Communication Settings',
            'use_school_number'              => 'Use School Phone Number (if enabled)',
            'use_personal_number'            => 'Use Personal Phone Number (if enabled by admin)',
            'send_fee_reminders_notification'=> 'Send Fee Reminder Notifications',
            'send_health_alerts'             => 'Send Health Alerts (Nurse Only)',
            'send_confidential_alerts'       => 'Send Confidential Counseling Alerts',
            'send_overdue_notifications'     => 'Send Library Overdue Notifications',
            'send_transport_alerts'          => 'Send Transport Delay/Route Alerts',
            'send_hostel_alerts'             => 'Send Hostel Gate Pass/Incident Alerts',
            'send_platform_announcements'    => 'Send Platform-Wide Announcements (SaaS)',
            'view_announcements'             => 'View School Announcements',
            'receive_notifications'          => 'Receive Notifications',
            'send_message_to_teacher'        => 'Send Message to Teacher (Student/Parent)',
            'send_message_to_school_office'  => 'Send Message to School Office (Parent)',
            'send_hr_announcements'          => 'Send HR Announcements',

            // ════════════════════════════════════════════════════════════
            // OPERATIONS — Library
            // ════════════════════════════════════════════════════════════
            'manage_books'                   => 'Manage Books (Add/Edit/Remove)',
            'manage_book_categories'         => 'Manage Book Categories',
            'issue_books'                    => 'Issue Books to Students',
            'return_books'                   => 'Return Books',
            'track_overdue_books'            => 'Track Overdue Books',
            'collect_library_fines'          => 'Collect Library Fines',
            'manage_digital_resources'       => 'Manage Digital Resources',
            'reserve_books'                  => 'Reserve Books (Student Self-Service)',
            'view_library_catalog'           => 'View Library Catalog',
            'view_own_library_issued_books'  => 'View Own Issued Books (Student)',
            'manage_library'                 => 'Manage Full Library Module',

            // ════════════════════════════════════════════════════════════
            // OPERATIONS — Transport
            // ════════════════════════════════════════════════════════════
            'manage_vehicles'                => 'Manage Vehicles',
            'manage_routes'                  => 'Manage Routes',
            'manage_drivers'                 => 'Manage Drivers',
            'assign_students_to_routes'      => 'Assign Students to Routes',
            'track_vehicle_locations'        => 'Track Vehicle Locations (GPS)',
            'manage_transport_fees'          => 'Manage Transport Fees (Input Only)',
            'record_vehicle_maintenance'     => 'Record Vehicle Maintenance',
            'manage_transport_incidents'     => 'Manage Transport Incidents',
            'view_own_transport_route'       => 'View Own Transport Route (Student)',
            'manage_transport'               => 'Manage Full Transport Module',

            // ════════════════════════════════════════════════════════════
            // OPERATIONS — Hostel
            // ════════════════════════════════════════════════════════════
            'manage_hostel_buildings'        => 'Manage Hostel Buildings',
            'manage_hostel_rooms'            => 'Manage Hostel Rooms',
            'manage_room_allocations'        => 'Manage Room Allocations',
            'manage_gate_passes'             => 'Manage Gate Passes',
            'track_student_check_in_out'     => 'Track Student Check-In/Out',
            'manage_hostel_fees'             => 'Manage Hostel Fees (Input Only)',
            'record_hostel_incidents'        => 'Record Hostel Incidents',
            'manage_hostel_inventory'        => 'Manage Hostel Inventory',
            'view_own_hostel_allocation'     => 'View Own Hostel Allocation (Student)',
            'view_gate_passes'               => 'View Gate Passes',
            'approve_gate_passes'            => 'Approve Gate Passes',
            'manage_hostel'                  => 'Manage Full Hostel Module',

            // ════════════════════════════════════════════════════════════
            // OPERATIONS — Cafeteria
            // ════════════════════════════════════════════════════════════
            'manage_menu'                    => 'Manage Cafeteria Menu',
            'manage_cafeteria_transactions'  => 'Manage Cafeteria Transactions',
            'manage_cafeteria_balance'       => 'Manage Student Meal Card Balance',
            'manage_cafeteria_inventory'     => 'Manage Cafeteria Inventory',
            'process_cafeteria_payments'     => 'Process Cafeteria Payments',
            'manage_dietary_preferences'     => 'Manage Dietary Preferences',
            'view_cafeteria_menu'            => 'View Cafeteria Menu',
            'manage_cafeteria'               => 'Manage Full Cafeteria Module',

            // ════════════════════════════════════════════════════════════
            // OPERATIONS — Visitors & Inventory
            // ════════════════════════════════════════════════════════════
            'manage_visitors'                => 'Manage Visitors',
            'manage_visitor_records'         => 'Manage Visitor Records',
            'issue_visitor_passes'           => 'Issue Visitor Passes',
            'manage_inquiries'               => 'Manage Admissions Inquiries',
            'manage_phone_directory'         => 'Manage Internal Phone Directory',
            'manage_inventory'               => 'Manage School Inventory',
            // Front Office (Admin Section port)
            'manage_complaints'              => 'Manage Complaints',
            'manage_postal'                  => 'Manage Postal Receive/Dispatch',
            'manage_phone_call_log'          => 'Manage Phone Call Log',
            'manage_front_office_setup'      => 'Manage Front Office Setup (reference lists)',

            // ════════════════════════════════════════════════════════════
            // HEALTH & COUNSELING (Confidential)
            // ════════════════════════════════════════════════════════════
            'view_health_summary'            => 'View Health Summary (Non-Confidential)',
            'view_health_records'            => 'View Full Health Records (Confidential)',
            'create_health_records'          => 'Create Health Records',
            'edit_health_records'            => 'Edit Health Records',
            'record_medical_visits'          => 'Record Medical Visits',
            'track_medications'              => 'Track Medications',
            'manage_vaccination_records'     => 'Manage Vaccination Records',
            'issue_medical_clearance'        => 'Issue Medical Clearance',
            'view_student_allergies'         => 'View Student Allergies',
            'flag_health_concerns'           => 'Flag Health Concerns',
            'unlock_confidential_health_record'    => 'Unlock Confidential Health Record (IH with Justification)',
            'view_counseling_summary'        => 'View Counseling Summary (Non-Confidential)',
            'view_counseling_records'        => 'View Full Counseling Records (Confidential)',
            'create_counseling_records'      => 'Create Counseling Records',
            'edit_counseling_records'        => 'Edit Counseling Records',
            'manage_behavior_records'        => 'Manage Behavior Records',
            'manage_iep_plans'               => 'Manage Individual Education Plans (IEP)',
            'schedule_counseling_sessions'   => 'Schedule Counseling Sessions',
            'log_counseling_notes'           => 'Log Counseling Notes (Confidential)',
            'flag_behavioral_concerns'       => 'Flag Behavioral Concerns',
            'unlock_confidential_counseling_record' => 'Unlock Confidential Counseling Record (IH with Justification)',

            // ════════════════════════════════════════════════════════════
            // SYSTEM & SETTINGS
            // ════════════════════════════════════════════════════════════
            'manage_school_settings'         => 'Manage School Settings',
            'manage_school_roles'            => 'Manage School Roles (Create Custom)',
            'manage_integrations'            => 'Manage Integrations (Biometric, SMS, etc.)',
            'view_audit_logs'                => 'View Audit Logs',
            'manage_backup'                  => 'Manage Backups',
            'approve_role_succession'        => 'Approve Role Succession Requests',
            'approve_school_admin_assignment'=> 'Approve New School Admin Assignment',
            'request_institute_head_assignment' => 'Request Institute Head Assignment (Escalates to SaaS Admin)',

            // ════════════════════════════════════════════════════════════
            // REPORTS & ANALYTICS
            // ════════════════════════════════════════════════════════════
            'view_all_reports'               => 'View All Reports',
            'export_all_reports'             => 'Export All Reports',
            'view_academic_analytics'        => 'View Academic Analytics',
            'view_financial_analytics'       => 'View Financial Analytics',
            'view_attendance_analytics'      => 'View Attendance Analytics',
            'view_staff_analytics'           => 'View Staff Analytics',
            'view_academic_reports'          => 'View Academic Reports',
            'view_financial_reports_limited' => 'View Financial Reports (Limited)',
            'view_staff_reports'             => 'View Staff Reports',
            'view_attendance_reports'        => 'View Attendance Reports',
            'view_admissions_reports'        => 'View Admissions Reports',
            'view_enrollment_reports'        => 'View Enrollment Reports',
            'export_student_data'            => 'Export Student Data',
            'view_library_reports'           => 'View Library Reports',
            'view_overdue_reports'           => 'View Overdue Reports',
            'export_library_reports'         => 'Export Library Reports',
            'view_transport_reports'         => 'View Transport Reports',
            'export_transport_reports'       => 'Export Transport Reports',
            'view_hostel_occupancy_reports'  => 'View Hostel Occupancy Reports',
            'view_gate_pass_history'         => 'View Gate Pass History',
            'view_cafeteria_reports'         => 'View Cafeteria Reports',
            'view_transaction_reports'       => 'View Transaction Reports',
            'view_visitor_reports'           => 'View Visitor Reports',
            'view_inquiry_reports'           => 'View Inquiry Reports',
            'view_health_reports'            => 'View Health Reports (Confidential)',
            'export_health_reports'          => 'Export Health Reports (Confidential)',
            'view_counseling_reports'        => 'View Counseling Reports (Confidential)',
            'export_counseling_reports'      => 'Export Counseling Reports (Confidential)',
            'view_hr_reports'                => 'View HR Reports',
            'view_leave_reports'             => 'View Leave Reports',
            'view_payroll_reports'           => 'View Payroll Reports',
            'view_performance_reports'       => 'View Performance Reports',
            'export_hr_reports'              => 'Export HR Reports',
            'view_fee_collection_reports'    => 'View Fee Collection Reports',
            'view_expense_reports'           => 'View Expense Reports',
            'view_class_attendance_report'   => 'View Class Attendance Report (Own)',
            'view_class_marks_report'        => 'View Class Marks Report (Own)',
            'export_reports'                 => 'Export Reports',
            'view_cross_school_reports'      => 'View Cross-School Reports (Multi-IH)',
            'export_cross_school_reports'    => 'Export Cross-School Reports (Multi-IH)',
        ];
    }

    /**
     * Maps role slug → array of permission slugs assigned to that role.
     */
    private function rolePermissions(): array
    {
        return [

            // ─────────────────────────────────────────────────────────
            // MULTI-INSTITUTE HEAD
            // (Inherits Institute Head + cross-school extras)
            // ─────────────────────────────────────────────────────────
            'multi_institute_head' => array_merge(
                $this->instituteHeadPermissions(),
                [
                    'view_cross_school_reports',
                    'export_cross_school_reports',
                ]
            ),

            // ─────────────────────────────────────────────────────────
            // INSTITUTE HEAD
            // ─────────────────────────────────────────────────────────
            'institute_head' => $this->instituteHeadPermissions(),

            // ─────────────────────────────────────────────────────────
            // SCHOOL ADMIN
            // ─────────────────────────────────────────────────────────
            'school_admin' => [
                // Student Management
                'view_students', 'create_student', 'edit_student',
                'delete_student', // approval required
                'change_student_status', // approval for critical statuses
                'view_student_documents', 'upload_student_documents',
                'manage_student_admissions',
                'enter_admission_marks',
                'conduct_admission_interview',
                'create_admission_tests',
                'manage_exam_attendance',
                'manage_student_transfers', // approval required
                'manage_student_promotions', // approval required
                'view_student_basic_info', 'view_all_student_profiles',
                // Academic
                'manage_classes', 'manage_sections', 'manage_subjects',
                'manage_timetable', 'manage_academic_calendar',
                'view_academic_calendar', 'assign_teachers_to_subjects',
                'view_attendance', 'edit_attendance',
                'manage_daily_activity_scores', 'view_classes', 'view_sections',
                // Attendance
                'mark_attendance_manual', 'mark_attendance_biometric',
                'mark_attendance_fallback', 'generate_attendance_report',
                'process_biometric_data',
                // Examination
                'view_marks',
                // Finance
                'view_fee_structures', 'create_fee_structure', 'edit_fee_structure',
                'view_fee_payments', 'record_fee_payment',
                'void_fee_payment', // approval required
                'approve_refunds', 'issue_refunds', // above threshold = approval
                'manage_expenses', 'approve_expenses',
                'approve_large_expenses', // approval required
                'manage_accounts',
                'manage_wallet',
                'view_payroll', 'run_payroll', // approval required
                'manage_scholarships', 'approve_scholarships', // above threshold = approval
                'view_budget', 'send_fee_reminders',
                'view_financial_reports_limited',
                // HR
                'view_staff', 'create_staff', 'edit_staff',
                'delete_staff', // approval required
                'change_staff_status', // approval for sensitive statuses
                'assign_additional_role', // FREE — no approval for non-sensitive
                'assign_sensitive_role',  // approval required
                'remove_role_from_staff', // approval for sensitive
                'initiate_role_succession', // approval required
                'manage_leave_requests', 'approve_leave',
                'manage_staff_documents', 'manage_certifications', 'manage_contracts',
                // Communication
                'send_notifications_all', 'send_sms', 'send_email',
                'send_push_notifications', 'manage_announcement_board',
                'manage_school_communication_settings', 'send_notifications_to_staff',
                // Operations — Library (high-level + granular)
                'manage_library',
                'manage_books', 'manage_book_categories',
                'issue_books', 'return_books', 'track_overdue_books',
                'collect_library_fines', 'manage_digital_resources',
                // Operations — Transport (high-level + granular)
                'manage_transport',
                'manage_vehicles', 'manage_routes', 'manage_drivers',
                'assign_students_to_routes', 'track_vehicle_locations',
                'manage_transport_fees', 'record_vehicle_maintenance',
                'manage_transport_incidents',
                // Operations — Hostel (high-level + granular)
                'manage_hostel',
                'manage_hostel_buildings', 'manage_hostel_rooms',
                'manage_room_allocations',
                'manage_gate_passes', 'view_gate_passes', 'approve_gate_passes',
                'track_student_check_in_out', 'manage_hostel_fees',
                'record_hostel_incidents', 'manage_hostel_inventory',
                // Operations — Cafeteria (high-level + granular)
                'manage_cafeteria',
                'manage_menu', 'manage_cafeteria_transactions',
                'manage_cafeteria_balance', 'manage_cafeteria_inventory',
                'process_cafeteria_payments', 'manage_dietary_preferences',
                // Operations — Visitors & Inventory (high-level + granular)
                'manage_visitors', 'manage_visitor_records',
                'issue_visitor_passes', 'manage_inquiries',
                'manage_phone_directory', 'manage_inventory',
                // Front Office (Admin Section port)
                'manage_complaints', 'manage_postal',
                'manage_phone_call_log', 'manage_front_office_setup',
                // Staff Attendance
                'manage_staff_attendance',
                // Health (read-only confidential view allowed)
                'view_health_summary', 'view_counseling_summary',
                'view_health_records', 'view_counseling_records',
                // Behavior records (admin can view)
                'manage_behavior_records',
                // System
                'manage_school_settings', 'manage_school_roles',
                'manage_integrations', 'view_audit_logs',
                'approve_school_admin_assignment',
                'request_institute_head_assignment',
                // Reports
                'view_academic_reports', 'view_attendance_reports',
                'view_financial_reports_limited', 'view_staff_reports', 'export_reports',
                'view_admissions_reports', 'view_attendance_analytics',
                'view_all_reports', 'export_all_reports',
                'view_library_reports', 'view_transport_reports',
                'view_hostel_occupancy_reports', 'view_gate_pass_history',
                'view_cafeteria_reports', 'view_visitor_reports',
                'view_hr_reports', 'view_fee_collection_reports', 'view_expense_reports', 'view_payroll_reports',
            ],


            // ─────────────────────────────────────────────────────────
            // TEACHER
            // ─────────────────────────────────────────────────────────
            'teacher' => [
                // Student (own class only — enforced via policy)
                'view_students', 'view_student_basic_info',
                // Academic
                'view_classes', 'view_sections', 'view_academic_calendar',
                'view_own_timetable',
                // Ported features (Phases 4/6/7)
                'manage_question_bank', 'manage_lesson_plans', 'manage_study_materials',
                'manage_download_center', 'manage_events', 'use_chat',
                'mark_attendance_manual',       // always available for own class
                'mark_attendance_biometric',    // when device is on
                'mark_attendance_fallback',     // when device is offline
                'edit_attendance',
                'manage_homework', 'manage_assignments',
                'enter_daily_activity_scores',
                // Examination
                'enter_marks', 'view_marks', 'edit_marks',
                // Communication
                'send_notifications_to_class',
                'use_school_number', 'use_personal_number',
                // Reports
                'view_class_attendance_report',
                'view_class_marks_report',
            ],

            // ─────────────────────────────────────────────────────────
            // ATTENDANCE CLERK
            // (Stackable secondary role on any Teacher or Staff)
            // ─────────────────────────────────────────────────────────
            'attendance_clerk' => [
                'mark_attendance_manual',
                'mark_attendance_biometric',
                'process_biometric_data',
                'view_attendance',
                'edit_attendance',
                'generate_attendance_report',
                'view_student_basic_info',
            ],

            // ─────────────────────────────────────────────────────────
            // REGISTRAR
            // ─────────────────────────────────────────────────────────
            'registrar' => [
                'view_students', 'create_student', 'edit_student',
                'view_student_basic_info',
                'manage_student_admissions',
                'manage_student_transfers',
                'issue_certificates',
                'upload_student_documents', 'view_student_documents',
                'change_student_status',
                'archive_student_records',
                'view_classes', 'view_sections',
                'send_notifications_to_class', // for admission notices
                'view_admissions_reports',
                'view_enrollment_reports',
                'export_student_data',
            ],

            // ─────────────────────────────────────────────────────────
            // BURSAR / ACCOUNTANT
            // ─────────────────────────────────────────────────────────
            'bursar' => [
                'view_fee_structures', 'create_fee_structure',
                'edit_fee_structure', 'delete_fee_structure',
                'view_fee_payments', 'record_fee_payment', 'edit_fee_payment',
                'void_fee_payment', 'issue_refunds', 'approve_refunds',
                'manage_expenses', 'approve_expenses',
                'manage_accounts',
                'manage_wallet',
                'manage_budget', 'view_budget',
                'manage_payroll', 'run_payroll', 'view_payroll',
                'manage_payroll_inputs',
                'manage_scholarships', 'apply_scholarship_discount',
                'manage_invoices', 'generate_fee_receipts',
                'manage_accounts_receivable', 'manage_accounts_payable',
                'reconcile_accounts', 'write_off_debt',
                'view_staff', 'view_payroll_records',
                'send_fee_reminders',
                'view_financial_reports', 'export_financial_reports',
                'view_fee_collection_reports', 'view_expense_reports',
                'view_payroll_reports', 'generate_balance_sheet',
            ],

            // ─────────────────────────────────────────────────────────
            // HR MANAGER
            // ─────────────────────────────────────────────────────────
            'hr_manager' => [
                'view_staff', 'create_staff', 'edit_staff',
                'manage_staff_documents', 'manage_certifications',
                'manage_leave_requests', 'approve_leave', 'view_leave_balance',
                'manage_performance_reviews',
                'manage_teacher_evaluations', 'use_chat',
                'view_payroll', 'manage_payroll_inputs',
                'manage_staff_attendance',
                'manage_contracts',
                'manage_staff_onboarding', 'manage_staff_offboarding',
                'send_notifications_to_staff', 'send_hr_announcements',
                'view_staff_reports', 'view_leave_reports',
                'view_payroll_reports', 'view_performance_reports',
                'export_hr_reports',
            ],

            // ─────────────────────────────────────────────────────────
            // NURSE / HEALTH OFFICER
            // ─────────────────────────────────────────────────────────
            'nurse' => [
                'view_student_basic_info',
                'view_health_records',
                'create_health_records', 'edit_health_records',
                'record_medical_visits', 'track_medications',
                'manage_vaccination_records',
                'issue_medical_clearance',
                'view_student_allergies',
                'flag_health_concerns',
                'send_health_alerts',
                'view_health_reports', 'export_health_reports',
            ],

            // ─────────────────────────────────────────────────────────
            // COUNSELOR
            // ─────────────────────────────────────────────────────────
            'counselor' => [
                'view_student_basic_info',
                'view_counseling_records',
                'create_counseling_records', 'edit_counseling_records',
                'manage_behavior_records',
                'manage_iep_plans',
                'schedule_counseling_sessions',
                'log_counseling_notes',
                'flag_behavioral_concerns',
                'send_confidential_alerts',
                'view_counseling_reports', 'export_counseling_reports',
                // Read-only academic summary (aggregate, non-confidential)
                'view_marks', // policy limits to summary only
                'view_attendance', // policy limits to summary only
            ],

            // ─────────────────────────────────────────────────────────
            // LIBRARIAN
            // ─────────────────────────────────────────────────────────
            'librarian' => [
                'view_student_basic_info',
                'manage_books', 'manage_book_categories',
                'issue_books', 'return_books',
                'track_overdue_books', 'collect_library_fines',
                'manage_digital_resources',
                'reserve_books', 'view_library_catalog',
                'send_overdue_notifications',
                'view_library_reports', 'view_overdue_reports',
                'export_library_reports',
            ],

            // ─────────────────────────────────────────────────────────
            // TRANSPORT MANAGER
            // ─────────────────────────────────────────────────────────
            'transport_manager' => [
                'view_student_basic_info',
                'manage_vehicles', 'manage_routes', 'manage_drivers',
                'assign_students_to_routes',
                'track_vehicle_locations',
                'manage_transport_fees',
                'record_vehicle_maintenance',
                'manage_transport_incidents',
                'send_transport_alerts',
                'view_transport_reports', 'export_transport_reports',
            ],

            // ─────────────────────────────────────────────────────────
            // HOSTEL WARDEN
            // ─────────────────────────────────────────────────────────
            'hostel_warden' => [
                'view_student_basic_info',
                'manage_hostel_buildings', 'manage_hostel_rooms',
                'manage_room_allocations',
                'manage_gate_passes', 'view_gate_passes',
                'track_student_check_in_out',
                'manage_hostel_fees',
                'record_hostel_incidents',
                'manage_hostel_inventory',
                'send_hostel_alerts',
                'view_hostel_occupancy_reports', 'view_gate_pass_history',
            ],

            // ─────────────────────────────────────────────────────────
            // CAFETERIA MANAGER
            // ─────────────────────────────────────────────────────────
            'cafeteria_manager' => [
                'manage_menu',
                'manage_cafeteria_transactions',
                'manage_cafeteria_balance',
                'manage_cafeteria_inventory',
                'process_cafeteria_payments',
                'manage_dietary_preferences',
                'view_cafeteria_reports',
                'view_transaction_reports',
            ],

            // ─────────────────────────────────────────────────────────
            // RECEPTIONIST / FRONT OFFICE
            // ─────────────────────────────────────────────────────────
            'receptionist' => [
                'view_student_basic_info',
                'manage_visitors', 'manage_visitor_records',
                'issue_visitor_passes',
                'manage_inquiries',
                'manage_phone_directory',
                // Front Office (Admin Section port)
                'manage_complaints', 'manage_postal',
                'manage_phone_call_log', 'manage_front_office_setup',
                'view_announcements',
                'receive_notifications',
                'view_visitor_reports', 'view_inquiry_reports',
            ],

            // ─────────────────────────────────────────────────────────
            // STUDENT
            // ─────────────────────────────────────────────────────────
            'student' => [
                'view_own_student_profile',
                'view_student_documents',
                'view_own_timetable',
                'view_own_attendance',
                'view_own_homework', 'submit_homework',
                'view_own_assignments', 'submit_assignment',
                'view_own_daily_activity_scores',
                'view_own_marks',
                'view_own_report_card',
                'view_own_exam_schedule',
                'view_own_fee_statement',
                'view_own_receipts',
                'view_announcements',
                'receive_notifications',
                'send_message_to_teacher',
                'view_library_catalog',
                'view_own_library_issued_books',
                'reserve_books',
                'view_own_transport_route',
                'view_own_hostel_allocation',
                'view_cafeteria_menu',
            ],

            // ─────────────────────────────────────────────────────────
            // PARENT
            // ─────────────────────────────────────────────────────────
            'parent' => [
                'view_own_student_profile', // scoped to child
                'view_student_documents',   // scoped to child
                'view_own_timetable',       // child timetable
                'view_own_attendance',      // child attendance
                'view_own_homework',        // child homework
                'view_own_assignments',     // child assignments
                'view_own_daily_activity_scores',
                'view_own_marks',
                'view_own_report_card',
                'view_own_exam_schedule',
                'view_child_fee_statement',
                'view_own_receipts',
                'pay_fee_online',
                'view_announcements',
                'receive_notifications',
                'send_message_to_teacher',
                'send_message_to_school_office',
                'view_own_transport_route',
                'view_own_hostel_allocation',
                'view_own_library_issued_books',
                'view_cafeteria_menu',
            ],
        ];
    }

    /**
     * Full permission list for Institute Head.
     * Extracted to a method so Multi-Institute Head can merge it easily.
     */
    private function instituteHeadPermissions(): array
    {
        return [
            // Student Management — Full
            'view_students', 'create_student', 'edit_student', 'delete_student',
            'change_student_status', 'view_student_documents', 'upload_student_documents',
            'manage_student_admissions',
            'enter_admission_marks',
            'conduct_admission_interview',
            'create_admission_tests',
            'manage_exam_attendance',
            'manage_student_transfers', 'manage_student_promotions',
            'view_all_student_profiles', 'archive_student_records', 'issue_certificates',
            'view_student_basic_info',
            // Academic — Full
            'manage_classes', 'manage_sections', 'manage_subjects',
            'manage_timetable', 'manage_academic_calendar', 'view_academic_calendar',
            'assign_teachers_to_subjects',
            'manage_homework', 'manage_assignments',
            'view_attendance', 'edit_attendance',
            'manage_daily_activity_scores', 'view_classes', 'view_sections',
            'mark_attendance_manual', 'mark_attendance_biometric',
            'mark_attendance_fallback', 'generate_attendance_report', 'process_biometric_data',
            // Examination — Full
            'manage_exam_schedules', 'manage_exam_types', 'manage_grading_scales',
            'enter_marks', 'edit_marks', 'edit_marks_published', 'delete_marks',
            'approve_mark_modifications', 'publish_results', 'generate_report_cards',
            'view_marks',
            // Finance — Full
            'view_fee_structures', 'create_fee_structure', 'edit_fee_structure', 'delete_fee_structure',
            'view_fee_payments', 'record_fee_payment', 'void_fee_payment',
            'approve_refunds', 'issue_refunds',
            'manage_expenses', 'approve_expenses', 'approve_large_expenses',
            'manage_accounts',
            'manage_wallet',
            // Phases 4/6/7/8
            'manage_question_bank', 'manage_online_exams', 'manage_exam_plan', 'manage_exam_settings',
            'manage_lesson_plans', 'manage_study_materials', 'manage_download_center', 'manage_teacher_evaluations',
            'manage_events', 'use_chat', 'view_user_logs', 'manage_login_permissions',
            'manage_appearance_settings', 'manage_system_utilities', 'manage_modules', 'manage_custom_fields',
            'manage_budget', 'view_budget',
            'manage_payroll', 'run_payroll', 'view_payroll',
            'manage_scholarships', 'approve_scholarships',
            'manage_invoices', 'generate_fee_receipts',
            'manage_accounts_receivable', 'manage_accounts_payable',
            'reconcile_accounts', 'generate_balance_sheet', 'write_off_debt',
            'send_fee_reminders', 'view_financial_reports', 'export_financial_reports',
            // HR — Full
            'view_staff', 'create_staff', 'edit_staff', 'delete_staff',
            'change_staff_status',
            'manage_staff_documents', 'manage_certifications',
            'manage_staff_attendance', 'manage_contracts',
            'manage_staff_onboarding', 'manage_staff_offboarding',
            'manage_performance_reviews',
            'manage_leave_requests', 'approve_leave', 'view_leave_balance',
            'manage_payroll', 'run_payroll',
            'assign_additional_role', 'assign_sensitive_role',
            'remove_role_from_staff',
            'approve_role_succession', 'approve_school_admin_assignment',
            'request_institute_head_assignment',
            // Communication
            'send_notifications_all', 'send_sms', 'send_email',
            'send_push_notifications', 'manage_announcement_board',
            'manage_school_communication_settings',
            // Operations — Full
            'manage_library', 'manage_transport', 'manage_hostel',
            'manage_cafeteria', 'manage_visitors', 'manage_inventory',
            'view_gate_passes', 'approve_gate_passes',
            // Health & Counseling (unlocking with justification)
            'view_health_summary', 'unlock_confidential_health_record',
            'view_counseling_summary', 'unlock_confidential_counseling_record',
            // System
            'manage_school_settings', 'manage_school_roles',
            'manage_integrations', 'view_audit_logs', 'manage_backup',
            // Reports — Full
            'view_all_reports', 'export_all_reports',
            'view_academic_analytics', 'view_financial_analytics',
            'view_attendance_analytics', 'view_staff_analytics',
        ];
    }

    // ── Seeder Entry Point ────────────────────────────────────────

    public function run(): void
    {
        // Clear Spatie's permission cache before seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('🔐  Seeding RBAC permissions...');

        // 1. Create all permissions
        $allPermissions = $this->permissions();
        foreach ($allPermissions as $slug => $label) {
            Permission::firstOrCreate(
                ['name' => $slug, 'guard_name' => self::GUARD],
            );
        }

        $this->command?->info(sprintf('   ✅  %d permissions created.', count($allPermissions)));

        // 2. Create roles and assign permissions
        $rolePermissions = $this->rolePermissions();
        foreach ($rolePermissions as $roleSlug => $permSlugs) {
            $role = Role::firstOrCreate(
                ['name' => $roleSlug, 'guard_name' => self::GUARD],
            );

            // Sync permissions (additive on first seed; use syncPermissions to replace)
            $role->syncPermissions(
                Permission::where('guard_name', self::GUARD)
                          ->whereIn('name', $permSlugs)
                          ->get()
            );

            $this->command?->info(sprintf(
                '   ✅  Role [%s]: %d permissions assigned.',
                $roleSlug,
                count($permSlugs),
            ));
        }

        // 3. Clear cache again after seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('🎉  RBAC seeding complete.');
    }
}
