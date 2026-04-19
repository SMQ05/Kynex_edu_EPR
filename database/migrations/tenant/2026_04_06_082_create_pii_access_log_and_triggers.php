<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PG-9: Create PII access audit log table and triggers.
 *
 * Implements FERPA/GDPR audit trail for sensitive tables:
 * health_records, behavior_incidents, student_fees, exam_marks, students (PII columns).
 *
 * The trigger function captures the current app.user_role session variable
 * set by SetPostgresUserRole middleware, so every data change is attributed
 * to a role.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ── Step A: Create the audit log table ──────────────────────
        DB::statement("
            CREATE TABLE pii_access_log (
                id          bigserial PRIMARY KEY,
                table_name  varchar(100) NOT NULL,
                record_id   varchar(26)  NOT NULL,
                action      varchar(10)  NOT NULL,
                user_role   varchar(50),
                accessed_at timestamptz  DEFAULT now(),
                old_data    jsonb,
                new_data    jsonb
            )
        ");

        DB::statement('
            CREATE INDEX idx_pii_log_table_record
                ON pii_access_log(table_name, record_id)
        ');

        DB::statement('
            CREATE INDEX idx_pii_log_accessed_at
                ON pii_access_log(accessed_at DESC)
        ');

        // ── Step B: Create the audit trigger function ───────────────
        DB::statement("
            CREATE OR REPLACE FUNCTION log_pii_access()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO pii_access_log (
                    table_name, record_id, action, user_role, old_data, new_data
                ) VALUES (
                    TG_TABLE_NAME,
                    COALESCE(NEW.id::text, OLD.id::text),
                    TG_OP,
                    current_setting('app.user_role', true),
                    CASE WHEN TG_OP = 'DELETE' THEN to_jsonb(OLD) ELSE NULL END,
                    CASE WHEN TG_OP IN ('INSERT', 'UPDATE') THEN to_jsonb(NEW) ELSE NULL END
                );
                RETURN COALESCE(NEW, OLD);
            END;
            \$\$ LANGUAGE plpgsql SECURITY DEFINER
        ");

        // ── Step C: Attach triggers to sensitive tables ─────────────

        DB::statement('
            CREATE TRIGGER audit_health_records
                AFTER INSERT OR UPDATE OR DELETE ON health_records
                FOR EACH ROW EXECUTE FUNCTION log_pii_access()
        ');

        DB::statement('
            CREATE TRIGGER audit_behavior_incidents
                AFTER INSERT OR UPDATE OR DELETE ON behavior_incidents
                FOR EACH ROW EXECUTE FUNCTION log_pii_access()
        ');

        DB::statement('
            CREATE TRIGGER audit_student_fees
                AFTER INSERT OR UPDATE OR DELETE ON student_fees
                FOR EACH ROW EXECUTE FUNCTION log_pii_access()
        ');

        DB::statement('
            CREATE TRIGGER audit_exam_marks
                AFTER INSERT OR UPDATE OR DELETE ON exam_marks
                FOR EACH ROW EXECUTE FUNCTION log_pii_access()
        ');

        // Students: track PII column changes only
        DB::statement('
            CREATE TRIGGER audit_students
                AFTER UPDATE OF
                    first_name, last_name, date_of_birth, phone, email, address
                ON students
                FOR EACH ROW EXECUTE FUNCTION log_pii_access()
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS audit_students ON students');
        DB::statement('DROP TRIGGER IF EXISTS audit_exam_marks ON exam_marks');
        DB::statement('DROP TRIGGER IF EXISTS audit_student_fees ON student_fees');
        DB::statement('DROP TRIGGER IF EXISTS audit_behavior_incidents ON behavior_incidents');
        DB::statement('DROP TRIGGER IF EXISTS audit_health_records ON health_records');
        DB::statement('DROP FUNCTION IF EXISTS log_pii_access()');
        DB::statement('DROP TABLE IF EXISTS pii_access_log');
    }
};
