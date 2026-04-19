<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add CHECK constraints that were missed in migration 071.
 *
 * BEFORE RUNNING: verify no dirty data exists:
 *   SELECT * FROM salary_components WHERE default_value_paisas < 0;
 *   Fix any rows before running this migration.
 *
 * NOTE: A cross-table constraint (marks_obtained <= exam_schedules.full_marks)
 * cannot be a CHECK constraint — it requires a trigger or application-layer
 * validation. ExamService::saveMarks() already validates this in PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Salary component default values must be non-negative
        DB::statement('
            ALTER TABLE salary_components
                ADD CONSTRAINT chk_salary_default_non_negative
                CHECK (default_value_paisas >= 0)
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE salary_components DROP CONSTRAINT IF EXISTS chk_salary_default_non_negative');
    }
};
