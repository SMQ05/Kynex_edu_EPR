<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add partial indexes missed in migration 072.
 *
 * - Overdue book issues: librarian alert widget
 * - Active staff: HR queries filter by is_active = true
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Issued book issues — librarian queries filter by status = 'issued'
        DB::statement("
            CREATE INDEX idx_book_issues_overdue
                ON book_issues(due_date)
                WHERE status = 'issued'
        ");

        // Active staff — HR queries always filter is_active = true
        DB::statement("
            CREATE INDEX idx_staff_active
                ON school_users(campus_id, active_role)
                WHERE is_active = true
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_staff_active');
        DB::statement('DROP INDEX IF EXISTS idx_book_issues_overdue');
    }
};
