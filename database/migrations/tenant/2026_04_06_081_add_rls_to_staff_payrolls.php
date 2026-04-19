<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add Row Level Security to staff_payrolls table.
 * Restricts payroll data access to HR and admin roles.
 *
 * Syntax matches migrations 062 and 076 exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE staff_payrolls ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE staff_payrolls FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY payroll_role_policy ON staff_payrolls
                USING (
                    current_setting('app.user_role', true) IN (
                        'SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD',
                        'HR_MANAGER'
                    )
                )
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS payroll_role_policy ON staff_payrolls');
        DB::statement('ALTER TABLE staff_payrolls DISABLE ROW LEVEL SECURITY');
    }
};
