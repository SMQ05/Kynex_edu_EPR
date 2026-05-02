<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extend Row Level Security to core tables: students, student_fees,
 * exam_marks, and fee_payments.
 *
 * These policies use role-based access only.
 * Student self-access (viewing own record only) is enforced at the
 * application layer, not RLS. Adding student_id-based RLS requires
 * passing student_id as a session variable — future work.
 *
 * Syntax matches migration 062 exactly: current_setting('app.user_role', true)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ── STUDENTS table ───────────────────────────────────────
        DB::statement('ALTER TABLE students ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE students FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY students_role_policy ON students
                USING (
                    current_setting('app.user_role', true) IN (
                        'SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD',
                        'TEACHER', 'REGISTRAR', 'ATTENDANCE_CLERK',
                        'NURSE', 'COUNSELOR', 'RECEPTIONIST',
                        'BURSAR', 'ACCOUNTANT', 'HR_MANAGER',
                        'EXAM_ADMIN'
                    )
                    OR current_setting('app.user_role', true) = 'STUDENT'
                )
        ");

        // ── STUDENT_FEES table ───────────────────────────────────
        DB::statement('ALTER TABLE student_fees ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE student_fees FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY fees_role_policy ON student_fees
                USING (
                    current_setting('app.user_role', true) IN (
                        'SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD',
                        'BURSAR', 'ACCOUNTANT'
                    )
                    OR current_setting('app.user_role', true) IN ('STUDENT', 'PARENT')
                )
        ");

        // ── EXAM_MARKS table ─────────────────────────────────────
        DB::statement('ALTER TABLE exam_marks ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE exam_marks FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY exam_marks_role_policy ON exam_marks
                USING (
                    current_setting('app.user_role', true) IN (
                        'SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD',
                        'TEACHER', 'EXAM_ADMIN'
                    )
                    OR current_setting('app.user_role', true) IN ('STUDENT', 'PARENT')
                )
        ");

        // ── FEE_PAYMENTS table ───────────────────────────────────
        DB::statement('ALTER TABLE fee_payments ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE fee_payments FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY fee_payments_role_policy ON fee_payments
                USING (
                    current_setting('app.user_role', true) IN (
                        'SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD',
                        'BURSAR', 'ACCOUNTANT'
                    )
                    OR current_setting('app.user_role', true) IN ('STUDENT', 'PARENT')
                )
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS fee_payments_role_policy ON fee_payments');
        DB::statement('ALTER TABLE fee_payments DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS exam_marks_role_policy ON exam_marks');
        DB::statement('ALTER TABLE exam_marks DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS fees_role_policy ON student_fees');
        DB::statement('ALTER TABLE student_fees DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS students_role_policy ON students');
        DB::statement('ALTER TABLE students DISABLE ROW LEVEL SECURITY');
    }
};
