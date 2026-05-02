<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add CHECK constraints to enforce data integrity at the database level.
 *
 * Before running in production, verify no dirty data exists:
 *   SELECT * FROM exam_marks WHERE marks_obtained < 0;
 *   SELECT * FROM exam_marks em JOIN exam_schedules es ON es.id = em.exam_schedule_id WHERE em.marks_obtained > es.full_marks;
 *   SELECT * FROM exam_schedules WHERE full_marks <= 0 OR pass_marks < 0 OR pass_marks > full_marks;
 *   SELECT * FROM student_fees WHERE amount_paisas <= 0 OR discount_paisas < 0;
 *   SELECT * FROM students WHERE date_of_birth >= CURRENT_DATE;
 *   SELECT * FROM fee_payments WHERE total_amount_paisas <= 0;
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ── exam_marks: obtained marks must be non-negative ───────
        DB::statement('
            ALTER TABLE exam_marks
                ADD CONSTRAINT chk_marks_non_negative
                CHECK (marks_obtained >= 0)
        ');

        // ── exam_schedules: full_marks must be positive ──────────
        DB::statement('
            ALTER TABLE exam_schedules
                ADD CONSTRAINT chk_max_marks_positive
                CHECK (full_marks > 0)
        ');

        // ── exam_schedules: pass_marks must be valid range ───────
        DB::statement('
            ALTER TABLE exam_schedules
                ADD CONSTRAINT chk_pass_marks_valid
                CHECK (pass_marks >= 0 AND pass_marks <= full_marks)
        ');

        // ── student_fees: amount must be positive ────────────────
        DB::statement('
            ALTER TABLE student_fees
                ADD CONSTRAINT chk_fee_amount_positive
                CHECK (amount_paisas > 0)
        ');

        // ── student_fees: discount must be non-negative ──────────
        DB::statement('
            ALTER TABLE student_fees
                ADD CONSTRAINT chk_discount_non_negative
                CHECK (discount_paisas >= 0)
        ');

        // ── students: DOB must be in the past ────────────────────
        DB::statement('
            ALTER TABLE students
                ADD CONSTRAINT chk_dob_in_past
                CHECK (date_of_birth < CURRENT_DATE)
        ');

        // ── fee_payments: payment amount must be positive ────────
        DB::statement('
            ALTER TABLE fee_payments
                ADD CONSTRAINT chk_payment_positive
                CHECK (total_amount_paisas > 0)
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE fee_payments DROP CONSTRAINT IF EXISTS chk_payment_positive');
        DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS chk_dob_in_past');
        DB::statement('ALTER TABLE student_fees DROP CONSTRAINT IF EXISTS chk_discount_non_negative');
        DB::statement('ALTER TABLE student_fees DROP CONSTRAINT IF EXISTS chk_fee_amount_positive');
        DB::statement('ALTER TABLE exam_schedules DROP CONSTRAINT IF EXISTS chk_pass_marks_valid');
        DB::statement('ALTER TABLE exam_schedules DROP CONSTRAINT IF EXISTS chk_max_marks_positive');
        DB::statement('ALTER TABLE exam_marks DROP CONSTRAINT IF EXISTS chk_marks_non_negative');
    }
};
