<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add partial indexes for common filtered queries.
 * Partial indexes only index rows matching the WHERE clause,
 * resulting in smaller indexes and faster lookups for filtered queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Active students — most queries filter by status = 'enrolled'
        DB::statement("
            CREATE INDEX idx_students_active_by_class
                ON students(class_id, section_id, admission_number)
                WHERE status = 'enrolled'
        ");

        // Unpaid student fees — fee collection screen
        DB::statement("
            CREATE INDEX idx_fees_unpaid_by_student
                ON student_fees(student_id, due_date)
                WHERE status NOT IN ('paid', 'waived')
        ");

        // Active device tokens — push notification lookups
        DB::statement("
            CREATE INDEX idx_device_tokens_active
                ON device_tokens(school_user_id, platform)
                WHERE is_active = true
        ");

        // Unread notifications — bell widget query
        DB::statement("
            CREATE INDEX idx_notifications_unread
                ON in_app_notifications(user_id, created_at DESC)
                WHERE read_at IS NULL
        ");

        // Issued books — library active issues screen
        DB::statement("
            CREATE INDEX idx_book_issues_active
                ON book_issues(library_member_id, due_date)
                WHERE status = 'issued'
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS idx_book_issues_active');
        DB::statement('DROP INDEX IF EXISTS idx_notifications_unread');
        DB::statement('DROP INDEX IF EXISTS idx_device_tokens_active');
        DB::statement('DROP INDEX IF EXISTS idx_fees_unpaid_by_student');
        DB::statement('DROP INDEX IF EXISTS idx_students_active_by_class');
    }
};
