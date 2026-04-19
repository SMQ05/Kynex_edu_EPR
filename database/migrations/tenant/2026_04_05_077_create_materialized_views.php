<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create materialized views for attendance and fee collection analytics.
 * Uses DB::statement() because materialized views cannot use Schema builder.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ── Attendance summary per student per academic year ─────
        DB::statement('
            CREATE MATERIALIZED VIEW mv_student_attendance_summary AS
            SELECT
                ar.student_id,
                ar.academic_year_id,
                ar.class_id,
                ar.section_id,
                COUNT(*) as total_days,
                COUNT(*) FILTER (WHERE ar.status = \'present\') as present_days,
                COUNT(*) FILTER (WHERE ar.status = \'absent\') as absent_days,
                COUNT(*) FILTER (WHERE ar.status = \'late\') as late_days,
                ROUND(
                    100.0 * COUNT(*) FILTER (WHERE ar.status = \'present\')
                    / NULLIF(COUNT(*), 0),
                2) as attendance_percentage,
                MAX(ar.date) as last_updated_date
            FROM attendance_records ar
            GROUP BY
                ar.student_id, ar.academic_year_id,
                ar.class_id, ar.section_id
            WITH DATA
        ');

        DB::statement('
            CREATE UNIQUE INDEX ON mv_student_attendance_summary
                (student_id, academic_year_id)
        ');

        DB::statement('
            CREATE INDEX ON mv_student_attendance_summary
                (class_id, section_id, attendance_percentage)
        ');

        // ── Fee collection summary by month and fee type ────────
        // FeePayment links to StudentFee via FeePaymentItem.
        // We join through fee_payment_items -> student_fees to get fee_type_id.
        DB::statement('
            CREATE MATERIALIZED VIEW mv_fee_collection_summary AS
            SELECT
                DATE_TRUNC(\'month\', fp.payment_date) as month,
                sf.fee_type_id,
                ft.name as fee_type_name,
                COUNT(DISTINCT fp.student_id) as students_paid,
                SUM(fpi.amount_paisas) as total_collected_paisas,
                COUNT(DISTINCT fp.id) as transaction_count
            FROM fee_payments fp
            JOIN fee_payment_items fpi ON fpi.payment_id = fp.id
            JOIN student_fees sf ON sf.id = fpi.student_fee_id
            JOIN fee_types ft ON ft.id = sf.fee_type_id
            WHERE fp.payment_date IS NOT NULL
            GROUP BY
                DATE_TRUNC(\'month\', fp.payment_date),
                sf.fee_type_id, ft.name
            WITH DATA
        ');

        DB::statement('
            CREATE UNIQUE INDEX ON mv_fee_collection_summary
                (month, fee_type_id)
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_fee_collection_summary');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_student_attendance_summary');
    }
};
